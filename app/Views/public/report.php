<?php

/**
 * Event-Bericht: Titelbild, Text, Bilder.
 *
 * @var array<string,mixed>        $event
 * @var array<string,mixed>|null   $cover
 * @var list<array<string,mixed>> $images
 */
$base = '/e/' . $event['slug'];
?>
<section class="event-title-bar">
    <div class="wrap">
        <p class="muted"><a href="<?= e(url($base)) ?>"><?= e($event['short_name'] ?: $event['name']) ?></a> · <?= e(format_date_range($event['starts_on'], $event['ends_on'])) ?></p>
        <h1><?= e((string) ($event['report_title'] ?: t('Bericht'))) ?></h1>
    </div>
</section>
<?php require __DIR__ . '/_event-nav.php'; ?>

<article class="wrap report">
    <?php if ($cover !== null): ?>
        <figure class="report__cover">
            <img src="<?= e(upload_url((string) $cover['file'])) ?>" alt="<?= e((string) $cover['caption']) ?>">
            <?php if ((string) $cover['caption'] !== ''): ?><figcaption><?= e((string) $cover['caption']) ?></figcaption><?php endif; ?>
        </figure>
    <?php endif; ?>
    <div class="report__text rich-text"><?= text_blocks((string) $event['report_text']) ?></div>
    <?php if ($images !== []): ?>
        <div class="gallery-grid" style="--gallery-spalten: 3">
            <?php foreach ($images as $img): ?>
                <figure class="gallery-item">
                    <a href="<?= e(upload_url((string) $img['file'])) ?>" class="js-lightbox" data-caption="<?= e((string) $img['caption']) ?>">
                        <img src="<?= e(upload_url((string) $img['thumb'])) ?>" alt="<?= e((string) $img['caption']) ?>" loading="lazy">
                    </a>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p style="margin-top:2rem"><a class="btn btn--ghost btn--on-dark" href="<?= e(url($base)) ?>">← <?= e(t('Zum Event')) ?></a></p>
</article>
