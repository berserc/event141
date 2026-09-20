<?php

/**
 * Foto/Video eines Kaempfers (animierte Fightcard): Foto, Video in Schleife
 * oder Wechsel Foto -> Video. Beide Ecken eines Kampfs laufen synchron
 * (data-group = Kampf-ID), gesteuert von assets/js/media.js.
 *
 * @var array<string,mixed> $bout
 * @var string              $side  red|blue
 * @var bool                $lazy
 */
$photo = (string) ($bout[$side . '_photo'] ?? '');
$video = (string) ($bout[$side . '_video'] ?? '');
$mode  = $video !== '' ? (string) ($bout[$side . '_media'] ?: 'photo') : 'photo';
$name  = trim($bout[$side . '_first'] . ' ' . $bout[$side . '_last']);
$img   = $photo !== ''
    ? '<img src="' . e(upload_url($photo)) . '" alt="' . e($name) . '"' . (($lazy ?? true) ? ' loading="lazy"' : '') . '>'
    : '<span class="fighter-silhouette fighter-silhouette--' . e($side) . '" aria-hidden="true"></span>';

if ($mode === 'photo' || $photo === '') {
    echo $img;

    return;
}
?>
<span class="media-stack" data-mode="<?= e($mode) ?>" data-photo="<?= max(1, min(5, (int) $bout[$side . '_photo_sec'])) ?>" data-group="k<?= (int) $bout['id'] ?>"><?= $img ?><video src="<?= e(upload_url($video)) ?>" muted playsinline preload="metadata" disablepictureinpicture disableremoteplayback></video></span>
