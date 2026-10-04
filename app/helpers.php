<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Url;

/** HTML-Escaping fuer die Ausgabe in Templates. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/', array $query = []): string
{
    return Url::to($path, $query);
}

function asset(string $path): string
{
    return Url::asset($path);
}

function upload_url(string $path): string
{
    return Url::upload($path);
}

function csrf_field(): string
{
    return Csrf::field();
}

/** "03172 / 2197" -> "+3431722197" */
function tel_href(string $number): string
{
    $number = trim($number);

    if ($number === '') {
        return '';
    }

    $plus   = str_starts_with($number, '+');
    $digits = preg_replace('/\D+/', '', $number) ?? '';

    if ($digits === '') {
        return '';
    }

    if ($plus) {
        return '+' . $digits;
    }

    if (str_starts_with($digits, '00')) {
        return '+' . substr($digits, 2);
    }

    if (str_starts_with($digits, '0')) {
        return '+' . (string) Config::get('country_code', '43') . substr($digits, 1);
    }

    return $digits;
}

function tel_link(string $number, string $class = 'contact-link'): string
{
    $href = tel_href($number);

    if ($href === '') {
        return '';
    }

    return sprintf('<a class="%s" href="tel:%s">%s</a>', e($class), e($href), e(trim($number)));
}

function mail_link(string $address, string $class = 'contact-link'): string
{
    $address = trim($address);

    if ($address === '' || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
        return e($address);
    }

    return sprintf('<a class="%s" href="mailto:%s">%s</a>', e($class), e($address), e($address));
}

function link_out(string $url, string $label = '', string $class = 'contact-link'): string
{
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }

    $label = $label !== '' ? $label : preg_replace('#^https?://(www\.)?#i', '', $url) ?? $url;

    return sprintf(
        '<a class="%s" href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
        e($class),
        e($url),
        e(rtrim((string) $label, '/'))
    );
}

/**
 * Text in der Sprache der Oberflaeche. Der deutsche Text ist der Schluessel;
 * weitere Argumente fuellen %s/%d-Platzhalter: t('%d Kämpfe verteilt.', $n).
 * Gibt den Text UNMASKIERT zurueck – in Views mit e() ausgeben.
 */
function t(?string $text, mixed ...$args): string
{
    return \App\Core\I18n::translate((string) $text, $args);
}

/**
 * Text fuer die Suche vereinheitlichen: Kleinbuchstaben, Umlaute und Akzente
 * auf den Grundbuchstaben ("Müller", "MÜLLER" und "muller" sind gleich,
 * ebenso "Zupančič" und "zupancic"). Steht in SQL als fold(spalte) bereit.
 */
function fold_text(?string $text): string
{
    static $map = [
        'ä' => 'a', 'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ą' => 'a', 'ă' => 'a',
        'ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ő' => 'o', 'ø' => 'o',
        'ü' => 'u', 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ů' => 'u', 'ű' => 'u',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'ě' => 'e', 'ę' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ı' => 'i',
        'č' => 'c', 'ć' => 'c', 'ç' => 'c', 'š' => 's', 'ś' => 's', 'ş' => 's', 'ș' => 's',
        'ž' => 'z', 'ź' => 'z', 'ż' => 'z', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n',
        'ř' => 'r', 'ť' => 't', 'ț' => 't', 'ď' => 'd', 'đ' => 'd', 'ł' => 'l', 'ľ' => 'l',
        'ý' => 'y', 'ÿ' => 'y', 'ğ' => 'g', 'ß' => 'ss', 'æ' => 'ae', 'œ' => 'oe',
    ];

    return strtr(mb_strtolower((string) $text, 'UTF-8'), $map);
}

/**
 * Jahrgaenge zu einer Altersspanne im Wettkampfjahr: (13, 15, 2026) ->
 * "Jg. 2011–2013". Grundlage ist die Verbandsregel "Alter = Wettkampfjahr
 * minus Geburtsjahr" (WAKO, World Boxing, IFMA).
 */
function birth_years(?int $ageMin, ?int $ageMax, int $year): string
{
    if ($year <= 0) {
        return '';
    }

    if ($ageMin !== null && $ageMax !== null) {
        return $ageMin === $ageMax ? t('Jg. %d', $year - $ageMin) : t('Jg. %1$d–%2$d', $year - $ageMax, $year - $ageMin);
    }

    if ($ageMin !== null) {
        return t('Jg. %d und älter', $year - $ageMin);
    }

    return $ageMax !== null ? t('Jg. %d und jünger', $year - $ageMax) : '';
}

/** Aktuelle Sprache der Oberflaeche ("de", "en"). */
function lang(): string
{
    return \App\Core\I18n::lang();
}

/** Sprachumschalter (DE · EN) fuer die Kopfzeilen. */
function lang_switch(string $class = 'lang-switch'): string
{
    $out = [];

    foreach (array_keys(\App\Core\I18n::LANGS) as $code) {
        $out[] = $code === lang()
            ? '<strong aria-current="true">' . strtoupper($code) . '</strong>'
            : '<a href="' . e(\App\Core\I18n::switchUrl($code)) . '" hreflang="' . $code . '" rel="nofollow">' . strtoupper($code) . '</a>';
    }

    return '<span class="' . e($class) . '" aria-label="Sprache / Language">' . implode('<span aria-hidden="true"> · </span>', $out) . '</span>';
}

/** "2026-07-27" -> "27.07.2026" (en: "27 Jul 2026") */
function format_date(?string $isoDate): string
{
    if ($isoDate === null || trim($isoDate) === '') {
        return '';
    }

    $ts = strtotime($isoDate);

    return $ts === false ? $isoDate : date(lang() === 'en' ? 'j M Y' : 'd.m.Y', $ts);
}

/** "2026-07-27" -> "Mo. 27.07.2026" (en: "Mon 27 Jul 2026") */
function format_date_long(?string $isoDate): string
{
    if ($isoDate === null || trim($isoDate) === '') {
        return '';
    }

    $ts = strtotime($isoDate);

    if ($ts === false) {
        return $isoDate;
    }

    if (lang() === 'en') {
        return date('D j M Y', $ts);
    }

    $tage = ['So.', 'Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.'];

    return $tage[(int) date('w', $ts)] . ' ' . date('d.m.Y', $ts);
}

/** Zeitraum "19.09.2026" oder "19.–21.09.2026". */
function format_date_range(?string $from, ?string $to): string
{
    if ($from === null || $from === '') {
        return '';
    }

    if ($to === null || $to === '' || $to === $from) {
        return format_date($from);
    }

    $a = strtotime($from);
    $b = strtotime($to);

    if ($a === false || $b === false) {
        return format_date($from) . ' – ' . format_date($to);
    }

    if (lang() === 'en') {
        return date('Y-m', $a) === date('Y-m', $b)
            ? date('j', $a) . '–' . date('j M Y', $b)
            : date('j M', $a) . ' – ' . date('j M Y', $b);
    }

    if (date('Y-m', $a) === date('Y-m', $b)) {
        return date('d.', $a) . '–' . date('d.m.Y', $b);
    }

    return date('d.m.', $a) . ' – ' . date('d.m.Y', $b);
}

function format_datetime(?string $iso): string
{
    if ($iso === null || trim($iso) === '') {
        return '';
    }

    $ts = strtotime($iso . ' UTC');

    return $ts === false ? $iso : date(lang() === 'en' ? 'j M Y H:i' : 'd.m.Y H:i', $ts);
}

/** "13:05:00" -> "13:05" */
function format_time(?string $time): string
{
    if ($time === null || trim($time) === '') {
        return '';
    }

    return substr($time, 0, 5);
}

function format_money(float|int|string|null $amount): string
{
    return lang() === 'en'
        ? '€ ' . number_format((float) $amount, 2, '.', ',')
        : number_format((float) $amount, 2, ',', '.') . ' €';
}

/** Gewicht 72.5 -> "72,5 kg" */
function format_weight(float|int|string|null $kg): string
{
    if ($kg === null || $kg === '' || (float) $kg <= 0) {
        return '';
    }

    $komma = lang() === 'en' ? '.' : ',';

    return rtrim(rtrim(number_format((float) $kg, 1, $komma, ''), '0'), $komma) . ' kg';
}

/** Alter in Jahren zum Stichtag (Standard: heute). */
function age_from(?string $birthdate, ?string $onDate = null): ?int
{
    if ($birthdate === null || trim($birthdate) === '') {
        return null;
    }

    try {
        $birth = new DateTimeImmutable($birthdate);
        $on    = new DateTimeImmutable($onDate ?: 'today');
    } catch (Exception) {
        return null;
    }

    return (int) $birth->diff($on)->y;
}

/** Erzeugt einen URL-tauglichen Slug aus einem deutschen Titel. */
function slugify(string $text): string
{
    $map  = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss'];
    $text = strtr($text, $map);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';

    return trim($text, '-');
}

/** Laesst nur eine kleine Menge harmloser Tags durch. */
function safe_html(string $html): string
{
    $html = preg_replace(
        '#<(script|style|iframe|object|embed|noscript|template|svg)\b[^>]*>.*?</\1\s*>#is',
        '',
        $html
    ) ?? $html;

    $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*#is', '', $html) ?? $html;
    $html = preg_replace('#<!--.*?-->#s', '', $html) ?? $html;

    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><a><blockquote><hr><table><thead><tbody><tr><th><td><small>';
    $clean   = strip_tags($html, $allowed);

    $clean = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
    $clean = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*(\2)/i', '$1="#"', $clean) ?? $clean;

    return $clean;
}

/** Wandelt Zeilenumbrueche eines Freitextfeldes in Absaetze. */
/**
 * Freitext mit Absaetzen und Zwischenueberschriften: Leerzeile = <p>, "## " am Zeilenanfang = <h3>.
 * $maxParagraphs > 0 kuerzt (Teaser).
 */
function text_blocks(string $text, int $maxParagraphs = 0): string
{
    $out = '';
    $n   = 0;
    foreach (preg_split('/\R{2,}/', trim(str_replace("\r\n", "\n", $text))) ?: [] as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        if (str_starts_with($block, '## ')) {
            $out .= '<h3>' . e(substr($block, 3)) . '</h3>';
            continue;
        }
        $out .= '<p>' . nl2br(e($block)) . '</p>';
        if ($maxParagraphs > 0 && ++$n >= $maxParagraphs) {
            break;
        }
    }

    return $out;
}

function nl2p(string $text): string
{
    $parts = preg_split('/\R{2,}/', trim($text)) ?: [];
    $out   = '';

    foreach ($parts as $part) {
        if (trim($part) === '') {
            continue;
        }

        $out .= '<p>' . nl2br(e($part)) . '</p>';
    }

    return $out;
}

function query(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;

    return is_string($value) ? trim($value) : $default;
}

function post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;

    return is_string($value) ? trim($value) : $default;
}

function post_int(string $key, int $default = 0): int
{
    $value = $_POST[$key] ?? null;

    return is_numeric($value) ? (int) $value : $default;
}

/** Wie post_int(), liefert aber NULL bei leerer Eingabe (Fremdschluessel). */
function post_id(string $key): ?int
{
    $value = post_int($key, 0);

    return $value > 0 ? $value : null;
}

function post_float(string $key, float $default = 0.0): float
{
    $value = $_POST[$key] ?? null;

    if (!is_string($value) && !is_numeric($value)) {
        return $default;
    }

    $normalized = str_replace([' ', "\u{00a0}"], '', (string) $value);
    $normalized = str_replace(',', '.', $normalized);

    return is_numeric($normalized) ? (float) $normalized : $default;
}

/** Wie post_float(), NULL bei leerem Feld. */
function post_float_or_null(string $key): ?float
{
    $raw = trim((string) ($_POST[$key] ?? ''));

    return $raw === '' ? null : post_float($key);
}

function post_bool(string $key): int
{
    return isset($_POST[$key]) && $_POST[$key] !== '' && $_POST[$key] !== '0' ? 1 : 0;
}

/** Normalisiert eine Datumseingabe auf YYYY-MM-DD. */
function parse_date(string $input): ?string
{
    $input = trim($input);

    if ($input === '') {
        return null;
    }

    foreach (['Y-m-d', 'd.m.Y', 'j.n.Y', 'd.m.y', 'd/m/Y'] as $format) {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $input);

        if ($date !== false && $date->format($format) === $input) {
            return $date->format('Y-m-d');
        }
    }

    $ts = strtotime($input);

    return $ts === false ? null : date('Y-m-d', $ts);
}

/** Normalisiert eine Uhrzeit auf HH:MM ('' = keine). */
function parse_time(string $input): string
{
    $input = trim($input);

    if ($input === '' || !preg_match('/^(\d{1,2})[:.](\d{2})/', $input, $m)) {
        return '';
    }

    $h = (int) $m[1];
    $i = (int) $m[2];

    if ($h > 23 || $i > 59) {
        return '';
    }

    return sprintf('%02d:%02d', $h, $i);
}

/** @return array{0:int,1:int,2:int} [seite, offset, seitenAnzahl] */
function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / max(1, $perPage)));
    $page  = max(1, min($page, $pages));

    return [$page, ($page - 1) * $perPage, $pages];
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/**
 * Tab-Leiste fuer zusammengehoerige Verwaltungsseiten.
 *
 * @param list<array{0:string,1:string}> $tabs [Label, Pfad] – aktiv ist der aktuelle Pfad
 */
function admin_tabs(array $tabs): string
{
    $current = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $html    = '<nav class="tabs" aria-label="' . e(t('Unterseiten')) . '">';

    foreach ($tabs as [$label, $path]) {
        $href   = url($path);
        $active = $current === $href;
        $html  .= '<a href="' . e($href) . '" class="tabs__tab' . ($active ? ' is-active' : '') . '"'
            . ($active ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
    }

    return $html . '</nav>';
}

/** Logo des Veranstalters unter assets/img/logo.* ('' = keines). */
function site_logo(): string
{
    static $logo = null;

    if ($logo !== null) {
        return $logo;
    }

    foreach (['svg', 'png', 'jpg', 'jpeg', 'webp'] as $ext) {
        if (is_file(dirname(__DIR__) . '/public/assets/img/logo.' . $ext)) {
            return $logo = asset('img/logo.' . $ext);
        }
    }

    return $logo = '';
}

/** Vollstaendiger Name fuer Listen. */
function person_name(array $row, bool $lastFirst = false): string
{
    $first = (string) ($row['first_name'] ?? '');
    $last  = (string) ($row['last_name'] ?? '');

    return trim($lastFirst ? $last . ' ' . $first : $first . ' ' . $last);
}

/** Flaggen-Emoji aus einem ISO-Laendercode (AT -> Flagge). */
function flag_emoji(string $iso): string
{
    $iso = strtoupper(trim($iso));

    if (!preg_match('/^[A-Z]{2}$/', $iso)) {
        return '';
    }

    $out = '';

    foreach (str_split($iso) as $c) {
        $out .= mb_chr(0x1F1E6 + ord($c) - ord('A'), 'UTF-8');
    }

    return $out;
}
