<?php

use App\Core\Auth;
use App\Models\AthleteRepo;

/**
 * @var list<array<string,mixed>> $athletes
 * @var list<array<string,mixed>> $gyms
 * @var string $q
 * @var int    $gymId
 */
?>
<div class="page-head">
    <h1>Sportler</h1>
    <?php if (Auth::canWrite()): ?>
        <div class="page-head__actions">
            <a class="btn btn--primary" href="<?= e(url('/admin/sportler/neu', ['gym' => $gymId ?: null])) ?>">Neuer Sportler</a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <form method="get" class="filters">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, Kampfname, Gym …">
        <select name="gym">
            <option value="">alle Gyms</option>
            <?php foreach ($gyms as $g): ?>
                <option value="<?= (int) $g['id'] ?>" <?= $gymId === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn--sm" type="submit">Filtern</button>
        <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/sportler')) ?>">Zurücksetzen</a>
    </form>

    <div class="table-scroll">
        <table class="table">
            <thead><tr><th></th><th>Name</th><th>Gym</th><th>Geb.</th><th>Geschlecht</th><th>Nation</th><th>Gewicht</th><th>Bilanz</th><th>Quelle</th></tr></thead>
            <tbody>
            <?php foreach ($athletes as $a): ?>
                <tr class="<?= (int) $a['active'] === 0 ? 'is-muted' : '' ?>">
                    <td class="col-thumb"><?php if ($a['photo_path'] !== ''): ?><img src="<?= e(upload_url($a['photo_path'])) ?>" alt="" width="40" height="40" loading="lazy" style="border-radius:50%;object-fit:cover"><?php endif; ?></td>
                    <td><a class="strong" href="<?= e(url('/admin/sportler/' . $a['id'])) ?>"><?= e($a['last_name']) ?> <?= e($a['first_name']) ?></a><?= $a['nickname'] !== '' ? ' <small class="muted">„' . e($a['nickname']) . '“</small>' : '' ?><?= (int) $a['active'] === 0 ? ' <span class="badge badge--muted">inaktiv</span>' : '' ?></td>
                    <td><a href="<?= e(url('/admin/gyms/' . $a['gym_id'])) ?>"><?= e($a['gym_name']) ?></a></td>
                    <td><?= e(format_date($a['birthdate'])) ?></td>
                    <td><?= e(AthleteRepo::GENDERS[$a['gender']] ?? $a['gender']) ?></td>
                    <td><?= e(flag_emoji($a['nationality'])) ?> <?= e($a['nationality']) ?></td>
                    <td><?= e(format_weight($a['weight'])) ?></td>
                    <td class="mono"><?= e(AthleteRepo::record($a)) ?></td>
                    <td><?= $a['gym141_member_id'] !== null ? '<span class="badge badge--info">Gym141</span>' : '' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($athletes === []): ?><tr><td colspan="9" class="empty">Keine Sportler gefunden.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
