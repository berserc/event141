<?php

use App\Models\AthleteRepo;

/**
 * Formularfelder eines Sportlers – gemeinsam fuer Verwaltung und Gym-Bereich.
 *
 * @var array<string,mixed>  $athlete
 * @var array<string,string> $errors
 * @var bool                 $ro   nur lesen
 */
$ro  = $ro ?? false;
$dis = $ro ? ' disabled' : '';
$err = static fn (string $f): string => isset($errors[$f]) ? '<p class="field__error">' . e($errors[$f]) . '</p>' : '';
?>
<div class="field-row">
    <div class="field field--grow"><label for="first_name"><?= e(t('Vorname')) ?> *</label><input id="first_name" name="first_name" required value="<?= e($athlete['first_name']) ?>"<?= $dis ?>><?= $err('first_name') ?></div>
    <div class="field field--grow"><label for="last_name"><?= e(t('Zuname')) ?> *</label><input id="last_name" name="last_name" required value="<?= e($athlete['last_name']) ?>"<?= $dis ?>></div>
    <div class="field field--sm"><label for="nickname"><?= e(t('Kampfname')) ?></label><input id="nickname" name="nickname" value="<?= e($athlete['nickname']) ?>" placeholder="<?= e(t('„The Hammer“')) ?>"<?= $dis ?>></div>
</div>
<div class="field-row">
    <div class="field field--sm"><label for="birthdate"><?= e(t('Geburtsdatum')) ?></label><input id="birthdate" name="birthdate" type="date" value="<?= e((string) $athlete['birthdate']) ?>"<?= $dis ?>><?= $err('birthdate') ?></div>
    <div class="field field--sm">
        <label for="gender"><?= e(t('Geschlecht')) ?></label>
        <select id="gender" name="gender"<?= $dis ?>>
            <?php foreach (AthleteRepo::GENDERS as $k => $l): ?>
                <option value="<?= e($k) ?>" <?= $athlete['gender'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field field--xs"><label for="nationality"><?= e(t('Nation')) ?></label><input id="nationality" name="nationality" maxlength="2" value="<?= e($athlete['nationality']) ?>" placeholder="AT"<?= $dis ?>></div>
    <div class="field field--xs"><label for="weight"><?= e(t('Gewicht (kg)')) ?></label><input id="weight" name="weight" inputmode="decimal" value="<?= $athlete['weight'] !== null && $athlete['weight'] !== '' ? e(number_format((float) $athlete['weight'], 1, ',', '')) : '' ?>"<?= $dis ?>></div>
    <div class="field field--xs"><label for="height"><?= e(t('Größe (cm)')) ?></label><input id="height" name="height" type="number" min="0" value="<?= e((string) $athlete['height']) ?>"<?= $dis ?>></div>
</div>
<div class="field-row">
    <div class="field field--xs"><label for="record_wins"><?= e(t('Siege')) ?></label><input id="record_wins" name="record_wins" type="number" min="0" value="<?= (int) $athlete['record_wins'] ?>"<?= $dis ?>></div>
    <div class="field field--xs"><label for="record_losses"><?= e(t('Niederl.')) ?></label><input id="record_losses" name="record_losses" type="number" min="0" value="<?= (int) $athlete['record_losses'] ?>"<?= $dis ?>></div>
    <div class="field field--xs"><label for="record_draws"><?= e(t('Unent.')) ?></label><input id="record_draws" name="record_draws" type="number" min="0" value="<?= (int) $athlete['record_draws'] ?>"<?= $dis ?>></div>
    <div class="field field--grow"><label for="email"><?= e(t('E-Mail')) ?></label><input id="email" name="email" type="email" value="<?= e($athlete['email']) ?>"<?= $dis ?>><?= $err('email') ?></div>
    <div class="field field--sm"><label for="phone"><?= e(t('Telefon')) ?></label><input id="phone" name="phone" value="<?= e($athlete['phone']) ?>"<?= $dis ?>></div>
</div>
<div class="field"><label for="bio"><?= e(t('Bio')) ?> <small><?= e(t('(Kampf-Detailseite)')) ?></small></label><textarea id="bio" name="bio" rows="4"<?= $dis ?>><?= e($athlete['bio'] ?? '') ?></textarea></div>
<div class="field-row">
    <div class="field field--xs"><label for="age"><?= e(t('Alter')) ?> <small><?= e(t('(ohne Geb.-Datum)')) ?></small></label><input id="age" name="age" type="number" min="0" value="<?= e((string) (athlete_age(null, $athlete['age'] ?? null, $athlete['age_year'] ?? null) ?? '')) ?>"<?= $dis ?>></div>
    <div class="field field--grow">
        <label for="video"><?= e(t('Kämpfer-Video')) ?> <small><?= e(t('(MP4/WebM, stumm, max. 100 MB – animierte Fightcard)')) ?></small></label>
        <?php if (($athlete['video_path'] ?? '') !== ''): ?>
            <video src="<?= e(upload_url($athlete['video_path'])) ?>" muted loop playsinline controls width="160" style="border-radius:8px;display:block"></video>
            <?php if (!$ro): ?><label class="check"><input type="checkbox" name="video_remove" value="1"> <?= e(t('Video entfernen')) ?></label><?php endif; ?>
        <?php endif; ?>
        <?php if (!$ro): ?><input id="video" type="file" name="video" accept="video/mp4,video/webm"><?php endif; ?>
    </div>
    <div class="field field--sm"><label for="media_mode"><?= e(t('Anzeige')) ?></label>
        <select id="media_mode" name="media_mode"<?= $dis ?>>
            <?php foreach (['photo' => t('nur Foto'), 'video' => t('Video (Schleife)'), 'both' => t('Foto ↔ Video im Wechsel')] as $k => $l): ?>
                <option value="<?= e($k) ?>" <?= ($athlete['media_mode'] ?? 'photo') === $k ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
        </select></div>
    <div class="field field--xs"><label for="photo_sec"><?= e(t('Foto-Sek.')) ?></label><input id="photo_sec" name="photo_sec" type="number" min="1" max="5" value="<?= (int) ($athlete['photo_sec'] ?? 3) ?>"<?= $dis ?>></div>
</div>
<div class="field"><label for="note"><?= e(t('Notiz')) ?></label><input id="note" name="note" value="<?= e($athlete['note']) ?>"<?= $dis ?>></div>
<div class="field-row">
    <div class="field field--grow">
        <label for="photo"><?= e(t('Foto')) ?></label>
        <?php if ($athlete['photo_path'] !== ''): ?>
            <img src="<?= e(upload_url($athlete['photo_path'])) ?>" alt="" width="96" height="96" style="border-radius:8px;object-fit:cover">
            <?php if (!$ro): ?><label class="check"><input type="checkbox" name="photo_remove" value="1"> <?= e(t('Foto entfernen')) ?></label><?php endif; ?>
        <?php endif; ?>
        <?php if (!$ro): ?><input id="photo" type="file" name="photo" accept="image/*"><?php endif; ?>
    </div>
    <div class="field">
        <input type="hidden" name="active_form" value="1">
        <label class="check"><input type="checkbox" name="active" value="1" <?= (int) $athlete['active'] === 1 ? 'checked' : '' ?><?= $dis ?>> <?= e(t('aktiv (kann angemeldet werden)')) ?></label>
    </div>
</div>
<?php if ($athlete['gym141_member_id'] !== null): ?>
    <p class="field__hint"><?= e($athlete['gym141_member_no'] !== ''
        ? t('Aus Gym141 übernommen (Mitglied #%d, Nr. %s). Beim nächsten Holen werden Name, Geburtsdatum und Kontakt aktualisiert.', (int) $athlete['gym141_member_id'], $athlete['gym141_member_no'])
        : t('Aus Gym141 übernommen (Mitglied #%d). Beim nächsten Holen werden Name, Geburtsdatum und Kontakt aktualisiert.', (int) $athlete['gym141_member_id'])) ?></p>
<?php endif; ?>
