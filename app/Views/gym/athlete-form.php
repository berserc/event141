<?php

use App\Models\AthleteRepo;

/**
 * @var array<string,mixed>  $athlete
 * @var array<string,string> $errors
 * @var bool                 $isNew
 */
$id     = (int) ($athlete['id'] ?? 0);
$action = $isNew ? url('/gym/sportler') : url('/gym/sportler/' . $id);
$err    = static fn (string $f): string => isset($errors[$f]) ? '<p class="m-error">' . e($errors[$f]) . '</p>' : '';
?>
<h1><?= $isNew ? 'Neuer Sportler' : e(person_name($athlete)) ?></h1>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="m-form">
    <?= csrf_field() ?>
    <div class="m-card">
        <div class="m-grid">
            <div class="m-field"><label for="first_name">Vorname *</label><input id="first_name" name="first_name" required value="<?= e($athlete['first_name']) ?>"><?= $err('first_name') ?></div>
            <div class="m-field"><label for="last_name">Zuname *</label><input id="last_name" name="last_name" required value="<?= e($athlete['last_name']) ?>"></div>
        </div>
        <div class="m-grid">
            <div class="m-field"><label for="nickname">Kampfname</label><input id="nickname" name="nickname" value="<?= e($athlete['nickname']) ?>"></div>
            <div class="m-field"><label for="birthdate">Geburtsdatum</label><input id="birthdate" name="birthdate" type="date" value="<?= e((string) $athlete['birthdate']) ?>"><?= $err('birthdate') ?></div>
        </div>
        <div class="m-grid m-grid--3">
            <div class="m-field"><label for="gender">Geschlecht</label>
                <select id="gender" name="gender">
                    <?php foreach (AthleteRepo::GENDERS as $k => $l): ?><option value="<?= e($k) ?>" <?= $athlete['gender'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                </select></div>
            <div class="m-field"><label for="nationality">Nation (ISO)</label><input id="nationality" name="nationality" maxlength="2" value="<?= e($athlete['nationality']) ?>"></div>
            <div class="m-field"><label for="weight">Gewicht (kg)</label><input id="weight" name="weight" inputmode="decimal" value="<?= $athlete['weight'] !== null && $athlete['weight'] !== '' ? e(number_format((float) $athlete['weight'], 1, ',', '')) : '' ?>"></div>
        </div>
        <div class="m-grid m-grid--3">
            <div class="m-field"><label for="record_wins">Siege</label><input id="record_wins" name="record_wins" type="number" min="0" value="<?= (int) $athlete['record_wins'] ?>"></div>
            <div class="m-field"><label for="record_losses">Niederlagen</label><input id="record_losses" name="record_losses" type="number" min="0" value="<?= (int) $athlete['record_losses'] ?>"></div>
            <div class="m-field"><label for="record_draws">Unentschieden</label><input id="record_draws" name="record_draws" type="number" min="0" value="<?= (int) $athlete['record_draws'] ?>"></div>
        </div>
        <div class="m-grid">
            <div class="m-field"><label for="height">Größe (cm)</label><input id="height" name="height" type="number" min="0" value="<?= e((string) $athlete['height']) ?>"></div>
            <div class="m-field"><label for="photo">Foto</label>
                <?php if ($athlete['photo_path'] !== ''): ?><img src="<?= e(upload_url($athlete['photo_path'])) ?>" alt="" class="m-avatar m-avatar--big"><label class="m-check"><input type="checkbox" name="photo_remove" value="1"> entfernen</label><?php endif; ?>
                <input id="photo" type="file" name="photo" accept="image/*"></div>
        </div>
        <div class="m-field"><label for="bio">Bio <small>(erscheint auf der Kampf-Seite)</small></label><textarea id="bio" name="bio" rows="4"><?= e($athlete['bio'] ?? '') ?></textarea></div>
        <div class="m-grid m-grid--3">
            <div class="m-field"><label for="video">Video <small>(MP4/WebM)</small></label>
                <?php if (($athlete['video_path'] ?? '') !== ''): ?><label class="m-check"><input type="checkbox" name="video_remove" value="1"> vorhandenes Video entfernen</label><?php endif; ?>
                <input id="video" type="file" name="video" accept="video/mp4,video/webm"></div>
            <div class="m-field"><label for="media_mode">Anzeige</label>
                <select id="media_mode" name="media_mode">
                    <?php foreach (['photo' => 'nur Foto', 'video' => 'Video (Schleife)', 'both' => 'Foto ↔ Video'] as $k => $l): ?>
                        <option value="<?= e($k) ?>" <?= ($athlete['media_mode'] ?? 'photo') === $k ? 'selected' : '' ?>><?= e($l) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="m-field"><label for="photo_sec">Foto-Dauer (1–5 s)</label><input id="photo_sec" name="photo_sec" type="number" min="1" max="5" value="<?= (int) ($athlete['photo_sec'] ?? 3) ?>"></div>
        </div>
        <div class="m-field"><label for="note">Notiz</label><input id="note" name="note" value="<?= e($athlete['note']) ?>"></div>
        <input type="hidden" name="active_form" value="1">
        <label class="m-check"><input type="checkbox" name="active" value="1" <?= (int) $athlete['active'] === 1 ? 'checked' : '' ?>> aktiv (kann angemeldet werden)</label>
    </div>

    <p>
        <button class="btn btn--primary" type="submit"><?= $isNew ? 'Sportler anlegen' : 'Speichern' ?></button>
        <a class="btn btn--ghost btn--on-dark" href="<?= e(url('/gym/sportler')) ?>">Abbrechen</a>
    </p>
</form>

<?php if (!$isNew): ?>
    <form method="post" action="<?= e(url('/gym/sportler/' . $id . '/loeschen')) ?>" data-confirm="Sportler löschen?">
        <?= csrf_field() ?><button class="linklike linklike--danger" type="submit">Sportler löschen</button>
    </form>
<?php endif; ?>
