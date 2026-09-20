<?php

/**
 * Drucklayout (A4 hoch, hell, druckerfreundlich) fuer Aushaenge und Listen.
 *
 * @var string $content
 * @var string $title
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?></title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: system-ui, "Segoe UI", Arial, sans-serif; font-size: 10.5pt; color: #111; margin: 0; padding: 12mm; background: #fff; }
        h1 { font-size: 18pt; margin: 0 0 1mm; text-transform: uppercase; letter-spacing: .02em; }
        h2 { font-size: 13pt; margin: 6mm 0 2mm; }
        .sub { color: #555; margin: 0 0 5mm; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: .3mm solid #999; padding: 1.3mm 2mm; text-align: left; vertical-align: middle; }
        th { background: #eee; font-size: 8.5pt; text-transform: uppercase; letter-spacing: .04em; }
        .num { text-align: center; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .red { border-left: 1.4mm solid #c8102e; }
        .blue { border-left: 1.4mm solid #1a5fb4; }
        .break td { background: #f3f3f3; text-align: center; font-style: italic; }
        .box { display: inline-block; width: 5mm; height: 5mm; border: .4mm solid #333; vertical-align: middle; }
        .box.on::after { content: "✓"; display: block; text-align: center; line-height: 4.4mm; font-weight: 700; }
        .fill { background: #fff8d6; min-width: 22mm; }
        small { color: #555; }
        .page { page-break-before: always; }
        .door { text-align: center; padding-top: 25mm; }
        .door h1 { font-size: 64pt; margin-bottom: 8mm; }
        .door li { font-size: 20pt; list-style: none; padding: 2mm 0; }
        .door ul { padding: 0; }
        .warn { border: .5mm solid #c8102e; color: #c8102e; padding: 2mm 3mm; margin-bottom: 4mm; font-weight: 700; }
        .toolbar { position: fixed; top: 4mm; right: 6mm; }
        .toolbar button { font: inherit; padding: 2mm 5mm; cursor: pointer; }
        @media print { body { padding: 0; } .toolbar { display: none; } }
    </style>
</head>
<body>
<div class="toolbar"><button type="button" onclick="window.print()">Drucken / als PDF speichern</button></div>
<?= $content ?>
</body>
</html>
