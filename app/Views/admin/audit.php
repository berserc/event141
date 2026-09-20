<?php

/**
 * @var list<array<string,mixed>> $entries
 * @var int $page
 * @var int $pages
 * @var int $total
 */
?>
<div class="page-head">
    <h1>Protokoll</h1>
    <p class="page-head__sub"><?= (int) $total ?> Einträge</p>
</div>

<div class="card">
    <div class="table-scroll">
        <table class="table table--compact">
            <thead><tr><th>Zeit</th><th>Benutzer</th><th>Aktion</th><th>Objekt</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($entries as $row): ?>
                <tr>
                    <td class="mono"><?= e(format_datetime((string) $row['created_at'])) ?></td>
                    <td><?= e($row['username']) ?></td>
                    <td><code><?= e($row['action']) ?></code></td>
                    <td><?= e($row['entity']) ?><?= $row['entity_id'] !== null ? ' #' . (int) $row['entity_id'] : '' ?></td>
                    <td class="wrap-anywhere"><?= e($row['detail']) ?></td>
                    <td class="mono"><?= e($row['ip']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($entries === []): ?>
                <tr><td colspan="6" class="empty">Noch keine Einträge.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Seiten">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="pager__current"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= e(url('/admin/protokoll', ['page' => $p])) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</div>
