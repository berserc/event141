<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

/**
 * Sprache der Oberflaeche. Quellsprache ist Deutsch: der deutsche Text ist
 * zugleich der Schluessel, die Uebersetzungen liegen als PHP-Dateien unter
 * app/lang/<sprache>/*.php (jede liefert ein Array 'Deutsch' => 'Uebersetzung').
 * Fehlt eine Uebersetzung, erscheint der deutsche Text.
 *
 * Welche Sprache gilt: Cookie "lang" (Umschalter in der Kopfzeile, ?lang=en),
 * sonst die Einstellung "default_lang", sonst Deutsch.
 */
final class I18n
{
    public const LANGS = [
        'de' => 'Deutsch',
        'en' => 'English',
    ];

    /**
     * Muster fuer Beschriftungen mit Zahl, die als fertiger Text gespeichert
     * sind (Runden im Turnierbaum, Tage im Aufbau).
     */
    private const PATTERNS = [
        'en' => [
            '/^Runde (\d+)$/u'  => 'Round $1',
            '/^Tag (\d+)$/u'    => 'Day $1',
            '/^Ring (\d+)$/u'   => 'Ring $1',
            '/^Kampf (\d+)$/u'  => 'Bout $1',
        ],
    ];

    private static ?string $lang = null;

    /** @var array<string,string>|null */
    private static ?array $dict = null;

    public static function lang(): string
    {
        if (self::$lang !== null) {
            return self::$lang;
        }

        $lang = (string) ($_COOKIE['lang'] ?? '');

        if (!isset(self::LANGS[$lang])) {
            try {
                $lang = Setting::get('default_lang');
            } catch (\Throwable) {
                $lang = '';   // Installer: noch keine Datenbank
            }
        }

        return self::$lang = isset(self::LANGS[$lang]) ? $lang : 'de';
    }

    public static function set(string $lang): void
    {
        self::$lang = isset(self::LANGS[$lang]) ? $lang : 'de';
        self::$dict = null;
    }

    /**
     * Umschalter: ?lang=en merkt die Sprache ein Jahr lang im Cookie und
     * leitet auf dieselbe Adresse ohne den Parameter zurueck.
     */
    public static function handleSwitch(): void
    {
        $lang = (string) ($_GET['lang'] ?? '');

        if (!isset(self::LANGS[$lang]) || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        setcookie('lang', $lang, [
            'expires'  => time() + 365 * 86400,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        header('Location: ' . self::urlWithout());
        exit;
    }

    /** Aktuelle Adresse mit ?lang=<sprache> (fuer den Umschalter). */
    public static function switchUrl(string $lang): string
    {
        $url = self::urlWithout();

        return $url . (str_contains($url, '?') ? '&' : '?') . 'lang=' . $lang;
    }

    /** Uebersetzt $text; mit $args wird das Ergebnis per vsprintf gefuellt. */
    public static function translate(string $text, array $args = []): string
    {
        $lang = self::lang();

        if ($lang !== 'de' && $text !== '') {
            $dict = self::dict($lang);

            if (isset($dict[$text])) {
                $text = $dict[$text];
            } else {
                foreach (self::PATTERNS[$lang] ?? [] as $muster => $ersatz) {
                    if (preg_match($muster, $text) === 1) {
                        $text = (string) preg_replace($muster, $ersatz, $text);
                        break;
                    }
                }
            }
        }

        return $args === [] ? $text : vsprintf($text, $args);
    }

    /** @return array<string,string> */
    public static function dict(string $lang): array
    {
        if (self::$dict !== null) {
            return self::$dict;
        }

        self::$dict = [];

        foreach (glob(dirname(__DIR__) . '/lang/' . $lang . '/*.php') ?: [] as $file) {
            $teil = require $file;

            if (is_array($teil)) {
                self::$dict = array_merge(self::$dict, $teil);
            }
        }

        return self::$dict;
    }

    private static function urlWithout(): string
    {
        $uri   = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $pfad  = (string) parse_url($uri, PHP_URL_PATH);
        $query = [];

        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        unset($query['lang']);

        return ($pfad !== '' ? $pfad : '/') . ($query !== [] ? '?' . http_build_query($query) : '');
    }
}
