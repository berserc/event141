<?php /** @var list<array<string,mixed>> $pages */ ?>
<div class="page-head">
    <h1>Seiten</h1>
    <div class="page-head__actions"><a class="btn btn--primary" href="<?= e(url('/admin/seiten/neu')) ?>">Neue Seite</a></div>
</div>

<div class="card">
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Titel</th><th>URL</th><th>Fußzeile</th><th>Sichtbar</th><th>Geändert</th></tr></thead>
            <tbody>
            <?php foreach ($pages as $p): ?>
                <tr>
                    <td><a class="strong" href="<?= e(url('/admin/seiten/' . $p['id'])) ?>"><?= e($p['title']) ?></a></td>
                    <td><a href="<?= e(url('/seite/' . $p['slug'])) ?>" target="_blank" rel="noopener">/seite/<?= e($p['slug']) ?></a></td>
                    <td><?= (int) $p['in_footer'] === 1 ? 'ja' : '' ?></td>
                    <td><?= (int) $p['published'] === 1 ? '<span class="pill pill--aktiv">ja</span>' : '<span class="pill pill--offen">nein</span>' ?></td>
                    <td><?= e(format_datetime((string) $p['updated_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
