<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;
use App\Models\EventRepo;
use App\Models\Setting;

final class SettingsController
{
    private const FIELDS = [
        'org_name', 'org_tagline', 'org_street', 'org_zip', 'org_city', 'org_email', 'org_phone',
        'org_website', 'home_title', 'home_text', 'home_event', 'win_methods',
    ];

    public function index(): void
    {
        AuthController::requireRole('superuser');

        View::display('admin/settings', [
            'title'    => 'Einstellungen',
            'settings' => Setting::all(),
            'events'   => EventRepo::all(),
        ], 'layouts/admin');
    }

    public function save(): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $values = [];

        foreach (self::FIELDS as $field) {
            $values[$field] = $field === 'home_text'
                ? safe_html((string) ($_POST[$field] ?? ''))
                : post($field);
        }

        if ($values['org_email'] !== '' && !filter_var($values['org_email'], FILTER_VALIDATE_EMAIL)) {
            Flash::error('Die E-Mail-Adresse des Veranstalters ist ungültig.');
            Url::redirect('/admin/einstellungen');
        }

        // Startseiten-Event muss existieren (oder leer = Eventliste).
        if ($values['home_event'] !== '' && EventRepo::findBySlug($values['home_event']) === null) {
            $values['home_event'] = '';
        }

        Setting::setMany($values);

        // Checkboxen fehlen im POST, wenn abgehakt – explizit speichern.
        Setting::set('public_site', post_bool('public_site') ? '1' : '0');
        Setting::set('gym_area', post_bool('gym_area') ? '1' : '0');
        Setting::set('gym_signup', post_bool('gym_signup') ? '1' : '0');

        // DevWorld-Lizenzschluessel: bei Aenderung sofort pruefen.
        $lizenz = trim(post('devworld_license_key'));

        if ($lizenz !== Setting::get('devworld_license_key')) {
            Setting::set('devworld_license_key', $lizenz);
            \App\Core\License::refresh();
        }

        Audit::log('settings_updated', 'settings');
        Flash::success('Einstellungen gespeichert.');
        Url::redirect('/admin/einstellungen');
    }

    public function checkLicense(): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        if (\App\Core\License::key() === '') {
            Flash::info('Kein Lizenzschlüssel hinterlegt – Event141 läuft in der Gratis-Version (' . \App\Core\License::FREE_EVENT_LIMIT . ' aktives Event).');
            Url::redirect('/admin/einstellungen');
        }

        $state = \App\Core\License::refresh();

        if (($state['reason'] ?? '') === 'unreachable') {
            Flash::error('Der Lizenzserver ist gerade nicht erreichbar – der letzte bekannte Stand gilt weiter.');
        } elseif (!empty($state['valid'])) {
            Flash::success('Lizenz gültig' . (empty($state['expires_at']) ? ' – unbefristet (Lifetime).' : ' bis ' . format_date(substr((string) $state['expires_at'], 0, 10)) . '.'));
        } else {
            Flash::error('Lizenz ungültig: ' . (string) ($state['reason'] ?? 'unbekannt'));
        }

        Url::redirect('/admin/einstellungen');
    }

    public function auditLog(): void
    {
        AuthController::requireRole('superuser');

        $page  = max(1, (int) query('page', '1'));
        $total = (int) Database::value('SELECT COUNT(*) FROM audit_log');

        [$page, $offset, $pages] = paginate($total, 100, $page);

        View::display('admin/audit', [
            'title'   => 'Protokoll',
            'entries' => Database::all('SELECT * FROM audit_log ORDER BY id DESC LIMIT 100 OFFSET ?', [$offset]),
            'page'    => $page,
            'pages'   => $pages,
            'total'   => $total,
        ], 'layouts/admin');
    }

    /** Siegarten aus den Einstellungen (eine je Zeile). @return list<string> */
    public static function winMethods(): array
    {
        $raw = Setting::get('win_methods', "Punkte\nKO\nTKO\nAufgabe\nDisqualifikation\nWalkover");

        return array_values(array_filter(array_map('trim', preg_split('/\R/', $raw) ?: [])));
    }
}
