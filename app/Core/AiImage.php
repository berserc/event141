<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * KI-Bildassistent der Bildbibliothek: Claude analysiert ein Bild (Motiv,
 * Bildtext, Schlagwoerter, Belichtung), die Pixelarbeit macht GD am Server –
 * Zuschnitt auf ein Zielformat rund ums erkannte Motiv und eine sanfte
 * Helligkeits-/Kontrastkorrektur. Das Original bleibt unveraendert, jeder
 * Zuschnitt wird als NEUES Bibliotheksbild gespeichert.
 *
 * Analysiert wird die kleine Vorschau (spart Kosten); das Motiv kommt als
 * relative Koordinaten zurueck und wird auf das grosse Bild umgerechnet.
 */
final class AiImage
{
    /** Zielformate: Schluessel => [Breite, Hoehe, Label (wird mit t() uebersetzt)]. */
    public const FORMATS = [
        'quadrat' => [1, 1, 'Quadratisch (1:1, Logos/Avatare)'],
        'hoch'    => [3, 4, 'Hochformat (3:4, Kämpferfotos)'],
        'breit'   => [16, 9, 'Breit (16:9, Titelbilder/Bericht)'],
        'galerie' => [3, 2, 'Galerie (3:2)'],
    ];

    /**
     * Bild analysieren: Bildtext, Schlagwoerter, Motiv-Ausschnitt, Belichtung.
     *
     * @param array<string,mixed> $img Zeile aus library_images (hydrated)
     * @return array{caption:string,tags:list<string>,subject:array{x:float,y:float,w:float,h:float},brightness:int,contrast:int}
     */
    public static function analyze(array $img): array
    {
        $thumb = rtrim((string) Config::get('upload_dir'), '/\\') . '/' . ltrim((string) $img['thumb'], '/');

        if (!is_file($thumb)) {
            // Notfalls das grosse Bild nehmen (alte Bestaende ohne Vorschau).
            $thumb = rtrim((string) Config::get('upload_dir'), '/\\') . '/' . ltrim((string) $img['file'], '/');
        }

        if (!is_file($thumb)) {
            throw new RuntimeException(t('Bild nicht gefunden.'));
        }

        $mime = (string) (mime_content_type($thumb) ?: 'image/jpeg');

        $zahl   = static fn (float $min, float $max): array => ['type' => 'number', 'minimum' => $min, 'maximum' => $max];
        $schema = [
            'type'       => 'object',
            'properties' => [
                'caption' => ['type' => 'string', 'description' => 'Bildtext, ein kurzer Satz'],
                'tags'    => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => '3 bis 6 kurze Schlagwörter, Kleinschreibung'],
                'subject' => [
                    'type'       => 'object',
                    'properties' => ['x' => $zahl(0, 1), 'y' => $zahl(0, 1), 'w' => $zahl(0, 1), 'h' => $zahl(0, 1)],
                    'required'   => ['x', 'y', 'w', 'h'],
                    'additionalProperties' => false,
                ],
                'brightness' => $zahl(-40, 40),
                'contrast'   => $zahl(-40, 40),
            ],
            'required'             => ['caption', 'tags', 'subject', 'brightness', 'contrast'],
            'additionalProperties' => false,
        ];

        $sprache   = lang() === 'en' ? 'Englisch' : 'Deutsch';
        $anweisung = 'Du bist Bildredakteur einer Kampfsport-Event-Plattform (Galas, Turniere, Sportler, Hallen, Publikum). '
            . 'Analysiere das Bild. caption: ein kurzer, sachlicher Bildtext auf ' . $sprache . ' (keine Namen raten). '
            . 'tags: 3–6 kurze Schlagwörter in Kleinschreibung auf ' . $sprache . ' (Motiv, Situation, z. B. "siegerehrung", "ring", "publikum"). '
            . 'subject: das Hauptmotiv als Rechteck in RELATIVEN Koordinaten (x/y = linke obere Ecke, w/h = Breite/Höhe, alles 0–1); '
            . 'bei Personen den Kopf mit etwas Luft darüber einschließen; füllt das Motiv das Bild, ist 0/0/1/1 richtig. '
            . 'brightness/contrast: empfohlene sanfte Korrektur von -40 bis 40 (0 = Bild passt so; brightness > 0 hellt auf, '
            . 'contrast > 0 erhöht den Kontrast). Konservativ bleiben – lieber 0 als zu viel.';

        $roh = Ai::request(
            [
                Ai::fileBlock($mime, $thumb),
                ['type' => 'text', 'text' => 'Bitte dieses Bild analysieren.'],
            ],
            $anweisung,
            $schema,
            1000
        );

        $klemme = static fn (mixed $v): float => max(0.0, min(1.0, (float) $v));
        $s      = (array) ($roh['subject'] ?? []);
        $x      = $klemme($s['x'] ?? 0);
        $y      = $klemme($s['y'] ?? 0);

        $tags = [];

        foreach ((array) ($roh['tags'] ?? []) as $tag) {
            $tag = mb_strtolower(trim((string) $tag), 'UTF-8');

            if ($tag !== '' && mb_strlen($tag) <= 40 && count($tags) < 6) {
                $tags[] = $tag;
            }
        }

        return [
            'caption'    => mb_substr(trim((string) ($roh['caption'] ?? '')), 0, 200),
            'tags'       => $tags,
            'subject'    => [
                'x' => $x,
                'y' => $y,
                'w' => max(0.05, min(1.0 - $x, $klemme($s['w'] ?? 1))),
                'h' => max(0.05, min(1.0 - $y, $klemme($s['h'] ?? 1))),
            ],
            'brightness' => max(-40, min(40, (int) round((float) ($roh['brightness'] ?? 0)))),
            'contrast'   => max(-40, min(40, (int) round((float) ($roh['contrast'] ?? 0)))),
        ];
    }

    /**
     * Zuschnitt auf ein Zielformat rund ums erkannte Motiv, optional mit
     * sanfter Korrektur; Ergebnis wird als neues Bibliotheksbild gespeichert.
     *
     * @param array<string,mixed> $img Zeile aus library_images (hydrated)
     * @return array{img:array{file:string,thumb:string,width:int,height:int},analyse:array<string,mixed>}
     */
    public static function crop(array $img, string $format, bool $verbessern, ?array $analyse = null): array
    {
        if (!isset(self::FORMATS[$format])) {
            throw new RuntimeException(t('Unbekanntes Zielformat.'));
        }

        $analyse ??= self::analyze($img);

        [$src, $png] = ImageTool::load((string) $img['file']);

        $w = imagesx($src);
        $h = imagesy($src);

        [$rw, $rh] = self::FORMATS[$format];
        $ratio     = $rw / $rh;

        // Groesstmoeglicher Ausschnitt im Zielformat …
        $cw = min($w, (int) round($h * $ratio));
        $ch = (int) round($cw / $ratio);

        if ($ch > $h) {
            $ch = $h;
            $cw = (int) round($h * $ratio);
        }

        // … zentriert auf das erkannte Motiv, an die Bildraender geklemmt.
        $s  = $analyse['subject'];
        $cx = ($s['x'] + $s['w'] / 2) * $w;
        $cy = ($s['y'] + $s['h'] / 2) * $h;

        $x = (int) round(max(0, min($w - $cw, $cx - $cw / 2)));
        $y = (int) round(max(0, min($h - $ch, $cy - $ch / 2)));

        $ausschnitt = imagecrop($src, ['x' => $x, 'y' => $y, 'width' => max(1, $cw), 'height' => max(1, $ch)]);

        if ($ausschnitt === false) {
            throw new RuntimeException(t('Das Bild konnte nicht zugeschnitten werden.'));
        }

        if ($verbessern) {
            if ($analyse['brightness'] !== 0) {
                imagefilter($ausschnitt, IMG_FILTER_BRIGHTNESS, (int) round($analyse['brightness'] * 1.5));
            }

            if ($analyse['contrast'] !== 0) {
                // GD zaehlt verkehrt: negative Werte ERHOEHEN den Kontrast.
                imagefilter($ausschnitt, IMG_FILTER_CONTRAST, -$analyse['contrast']);
            }
        }

        return ['img' => ImageTool::store($ausschnitt, $png), 'analyse' => $analyse];
    }
}
