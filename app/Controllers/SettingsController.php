<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Ticket141Client;
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

        $this->persist();

        Flash::success('Einstellungen gespeichert.');
        Url::redirect('/admin/einstellungen');
    }

    /** Speichern + Verbindung zu Ticket141 pruefen (ein Submit). */
    public function checkTicket141(): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $this->persist();

        $client = Ticket141Client::fromSettings();

        if (!$client->configured()) {
            Flash::info('Einstellungen gespeichert. Keine Ticket141-Adresse hinterlegt – die Kopplung ist aus.');
            Url::redirect('/admin/einstellungen');
        }

        try {
            $ping  = $client->ping();
            $key   = (array) ($ping['key'] ?? []);
            $scope = (string) ($key['scope'] ?? '');
            $text  = 'Verbindung zu Ticket141 ' . (string) ($ping['version'] ?? '') . ' steht (Schlüssel „' . (string) ($key['name'] ?? '') . '“, Rechte: ' . $scope . ').';

            if ($scope !== 'write') {
                $text .= ' Zum Anlegen von Events braucht der Schlüssel die Rechte „write“.';
            }

            if (($key['event'] ?? null) !== null) {
                $text .= ' Achtung: Der Schlüssel ist auf ein einzelnes Event beschränkt – damit lassen sich keine Events anlegen.';
            }

            Flash::success($text);
        } catch (\RuntimeException $e) {
            Flash::error('Einstellungen gespeichert, aber Ticket141 antwortet nicht: ' . $e->getMessage());
        }

        Url::redirect('/admin/einstellungen');
    }

    /** Alle Felder des Einstellungsformulars uebernehmen (bricht bei Fehlern mit Redirect ab). */
    private function persist(): void
    {
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

        $this->persistTicket141();

        Audit::log('settings_updated', 'settings');
    }

    /**
     * Ticket141-Kopplung: Adresse, Schluessel (leer = unveraendert), Widget.
     * Pro-Funktion – ohne Lizenz nur eine Meldung, die uebrigen Felder
     * werden trotzdem gespeichert.
     */
    private function persistTicket141(): void
    {
        $url   = trim(post('ticket141_url'));
        $key   = trim(post('ticket141_api_key'));
        $embed = post_bool('ticket141_embed') ? '1' : '0';

        if ($url !== '') {
            try {
                $url = Ticket141Client::normalizeUrl($url);
            } catch (\RuntimeException $e) {
                Flash::error($e->getMessage());
                Url::redirect('/admin/einstellungen');
            }
        }

        $geaendert = $url !== Setting::get('ticket141_url')
            || ($key !== '' && $key !== Setting::get('ticket141_api_key'))
            || $embed !== Setting::get('ticket141_embed', '0');

        if (!$geaendert) {
            return;
        }

        if (($pro = \App\Core\License::proFeatureError('Die Ticket141-Kopplung')) !== null) {
            Flash::error($pro);

            return;
        }

        Setting::set('ticket141_url', $url);
        Setting::set('ticket141_embed', $embed);

        if ($key !== '') {
            Setting::set('ticket141_api_key', $key);
        } elseif ($url === '') {
            // Kopplung aufgehoben: Schluessel nicht weiter aufheben.
            Setting::set('ticket141_api_key', '');
        }
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
