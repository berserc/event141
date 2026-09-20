<?php

use App\Models\AthleteRepo;

/**
 * @var array<string,mixed>       $gym
 * @var list<array<string,mixed>> $athletes
 */
?>
<div class="m-head">
    <h1>Sportler</h1>
    <p>
        <a class="btn btn--primary btn--sm" href="<?= e(url('/gym/sportler/neu')) ?>">Neuer Sportler</a>
        <a class="btn btn--ghost btn--on-dark btn--sm" href="<?= e(url('/gym/gym141')) ?>">Aus Gym141 holen</a>
    </p>
</div>

<div class="m-card">
    <?php if ($athletes === []): ?>
        <p class="muted">Noch keine Sportler. Legt sie manuell an oder holt sie aus Gym141.</p>
    <?php else: ?>
        <table class="m-table">
            <thead><tr><th>Name</th><th>Geb.</th><th>Gewicht</th><th>Bilanz</th><th>Anmeld.</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($athletes as $a): ?>
                <tr class="<?= (int) $a['active'] === 0 ? 'is-muted' : '' ?>">
                    <td>
                        <?php if ($a['photo_path'] !== ''): ?><img src="<?= e(upload_url($a['photo_path'])) ?>" alt="" class="m-avatar"><?php endif; ?>
                        <a href="<?= e(url('/gym/sportler/' . $a['id'])) ?>"><?= e($a['last_name']) ?> <?= e($a['first_name']) ?></a>
                        <?= $a['nickname'] !== '' ? '<small class="muted">„' . e($a['nickname']) . '“</small>' : '' ?>
                        <?= $a['gym141_member_id'] !== null ? '<small class="badge-dark">Gym141</small>' : '' ?>
                        <?= (int) $a['active'] === 0 ? '<small class="badge-dark">inaktiv</small>' : '' ?>
                    </td>
                    <td><?= e(format_date($a['birthdate'])) ?></td>
                    <td><?= e(format_weight($a['weight'])) ?></td>
                    <td><?= e(AthleteRepo::record($a)) ?></td>
                    <td><?= (int) $a['entry_count'] ?></td>
                    <td><a href="<?= e(url('/gym/sportler/' . $a['id'])) ?>">Bearbeiten</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
