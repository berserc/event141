<?php

/**
 * KI-Bildassistent (Core\Ai, Core\AiImage, Bildbibliothek, Einstellungen).
 */
return [
    // app/Core/Ai.php
    'Das inkludierte KI-Kontingent dieses Monats (%d Auswertungen) ist aufgebraucht. Mit eigenem API-Schlüssel (Einstellungen → KI) gibt es kein Limit.'
        => 'This month\'s included AI quota (%d analyses) is used up. With your own API key (Settings → AI) there is no limit.',
    'Kein Anthropic API-Schlüssel hinterlegt (Einstellungen → KI).' => 'No Anthropic API key stored (Settings → AI).',
    'API-Schlüssel ungültig – bitte in den Einstellungen prüfen.' => 'API key invalid – please check it in the settings.',
    'Die KI hat die Auswertung abgelehnt.'       => 'The AI declined to analyse this.',
    'Die Antwort der KI war unvollständig.'      => 'The AI response was incomplete.',
    'Unerwartete Antwort der KI.'                => 'Unexpected AI response.',
    'Verbindung zur Claude API fehlgeschlagen (%s).' => 'Connection to the Claude API failed (%s).',

    // app/Core/AiImage.php
    'Unbekanntes Zielformat.'                    => 'Unknown target format.',
    'Das Bild konnte nicht zugeschnitten werden.' => 'The image could not be cropped.',
    // Zielformat-Labels (AiImage::FORMATS, Ausgabe mit t($label))
    'Quadratisch (1:1, Logos/Avatare)'           => 'Square (1:1, logos/avatars)',
    'Hochformat (3:4, Kämpferfotos)'             => 'Portrait (3:4, fighter photos)',
    'Breit (16:9, Titelbilder/Bericht)'          => 'Wide (16:9, hero images/report)',
    'Galerie (3:2)'                              => 'Gallery (3:2)',

    // app/Controllers/ImageLibraryController.php
    '%d davon von der KI beschrieben und verschlagwortet.' => '%d of them captioned and tagged by the AI.',
    'KI-Bildtext fehlgeschlagen: %s'             => 'AI caption failed: %s',
    'KI-Bildtext übernommen: „%s“ – bitte kurz gegenlesen.' => 'AI caption applied: "%s" – please give it a quick read.',
    'KI-Zuschnitt fehlgeschlagen: %s'            => 'AI crop failed: %s',
    'KI-Zuschnitt gespeichert (%d × %d) – als neues Bild in der Bibliothek, das Original bleibt erhalten.'
        => 'AI crop saved (%d × %d) – as a new image in the library; the original is kept.',

    // app/Views/admin/medien/index.php
    'KI: Bildtext und Schlagwörter je Bild automatisch erzeugen (Bilder werden dafür an die Claude API übertragen)'
        => 'AI: generate a caption and tags for each image (images are sent to the Claude API for analysis)',
    'KI-Assistent'                               => 'AI assistant',
    'Bildtext & Tags erzeugen'                   => 'Generate caption & tags',
    'Helligkeit/Kontrast sanft korrigieren'      => 'Gently correct brightness/contrast',
    'Zuschnitt als neues Bild'                   => 'Crop as a new image',
    'Das Original bleibt erhalten.'              => 'The original is kept.',
    'KI arbeitet'                                => 'AI working',

    // app/Views/admin/settings.php
    'KI-Bildassistent (Claude API)'              => 'AI image assistant (Claude API)',
    'Mit einem Schlüssel beschreibt und verschlagwortet die KI Bilder der <strong>Bildbibliothek</strong> und schneidet sie rund ums Motiv auf Zielformate zu (Kämpferfoto 3:4, Titelbild 16:9 …). Die Bilder werden dafür zur Auswertung an die Claude API (Anthropic) übertragen.'
        => 'With a key, the AI captions and tags images in the <strong>image library</strong> and crops them around the subject to target formats (fighter photo 3:4, hero image 16:9 …). Images are sent to the Claude API (Anthropic) for analysis.',
    'Anthropic API-Schlüssel'                    => 'Anthropic API key',
    'Unter <a href="%s" target="_blank" rel="noopener">platform.claude.com</a> erstellen; abgerechnet wird nach Verbrauch direkt bei Anthropic (eine Auswertung kostet typischerweise unter einen Cent). Leer lassen = bleibt unverändert.'
        => 'Create one at <a href="%s" target="_blank" rel="noopener">platform.claude.com</a>; usage is billed directly by Anthropic (one analysis typically costs less than a cent). Leave empty = unchanged.',
    'Gespeicherten Schlüssel löschen'            => 'Delete the stored key',
    'Modell'                                     => 'Model',
    'Leer = <code>%s</code> (günstig und für Bilder ausreichend).' => 'Empty = <code>%s</code> (cheap and sufficient for images).',
];
