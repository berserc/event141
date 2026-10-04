<?php

/**
 * Galerien eines Events: Uebersicht (ohne $gallery) oder eine Galerie ($gallery + $images).
 *
 * @var array<string,mixed>        $event
 * @var list<array<string,mixed>> $galleries
 * @var array<string,mixed>|null   $gallery
 * @var list<array<string,mixed>> $images
 */
$base    = '/e/' . $event['slug'];
$gallery = $gallery ?? null;
$images  = $images ?? [];
?>
<section class="event-title-bar">
    <div class="wrap">
        <p class="muted"><a href="<?= e(url($base)) ?>"><?= e($event['short_name'] ?: $event['name']) ?></a><?= $gallery !== null ? ' · <a href="' . e(url($base . '/galerie')) . '">' . e(t('Galerie')) . '</a>' : '' ?></p>
        <h1><?= $gallery !== null ? e((string) $gallery['title']) : e(t('Galerie')) ?></h1>
    </div>
</section>
<?php require __DIR__ . '/_event-nav.php'; ?>

<section class="wrap page-section">
    <?php if ($gallery !== null): ?>
        <?php if (trim((string) $gallery['text']) !== ''): ?><div class="rich-text report__text"><?= text_blocks((string) $gallery['text']) ?></div><?php endif; ?>
        <?php if ($images === []): ?><p class="empty"><?= e(t('Noch keine Bilder.')) ?></p><?php endif; ?>
        <div class="gallery-grid" style="--gallery-spalten: 3">
            <?php foreach ($images as $img): ?>
                <figure class="gallery-item">
                    <a href="<?= e(upload_url((string) $img['file'])) ?>" class="js-lightbox" data-caption="<?= e((string) $img['caption']) ?>">
                        <img src="<?= e(upload_url((string) $img['thumb'])) ?>" alt="<?= e((string) $img['caption']) ?>" loading="lazy">
                    </a>
                    <?php if ((string) $img['caption'] !== ''): ?><figcaption><?= e((string) $img['caption']) ?></figcaption><?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
        <?php $others = array_values(array_filter($galleries, static fn (array $g): bool => (int) $g['id'] !== (int) $gallery['id'])); ?>
        <?php if ($others !== []): ?>
            <h2 class="section-heading" style="margin-top:3rem"><?= e(t('Weitere Galerien')) ?></h2>
            <div class="gallery-cards">
                <?php foreach ($others as $g): ?>
                    <a class="gallery-card" href="<?= e(url($base . '/galerie/' . $g['slug'])) ?>"><?php if (!empty($g['cover_thumb'])): ?><img src="<?= e(upload_url((string) $g['cover_thumb'])) ?>" alt="" loading="lazy"><?php endif; ?><strong><?= e((string) $g['title']) ?></strong><span><?= e(t('%d Bilder', (int) $g['image_count'])) ?></span></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($galleries === []): ?><p class="empty"><?= e(t('Noch keine Bilder – bald mehr.')) ?></p><?php endif; ?>
        <div class="gallery-cards">
            <?php foreach ($galleries as $g): ?>
                <a class="gallery-card" href="<?= e(url($base . '/galerie/' . $g['slug'])) ?>"><?php if (!empty($g['cover_thumb'])): ?><img src="<?= e(upload_url((string) $g['cover_thumb'])) ?>" alt="" loading="lazy"><?php endif; ?><strong><?= e((string) $g['title']) ?></strong><span><?= e(t('%d Bilder', (int) $g['image_count'])) ?></span></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
