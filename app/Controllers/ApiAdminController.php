<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;
use App\Core\Webhook;
use App\Models\ApiKeyRepo;
use App\Models\EventRepo;
use App\Models\GymRepo;

/** Plattform-API in der Verwaltung: Schluessel und Webhooks. */
final class ApiAdminController
{
    public function index(): void
    {
        AuthController::requireRole('superuser');

        // Frisch erzeugter Schluessel wird genau einmal angezeigt.
        $newKey = $_SESSION['new_api_key'] ?? null;
        unset($_SESSION['new_api_key']);

        View::display('admin/api', [
            'title'    => t('API & Kopplungen'),
            'keys'     => ApiKeyRepo::all(),
            'webhooks' => Database::all(
                'SELECT w.*, e.name AS event_name FROM webhooks w LEFT JOIN events e ON e.id = w.event_id ORDER BY w.id DESC'
            ),
            'events'   => EventRepo::all(),
            'gyms'     => GymRepo::options(),
            'newKey'   => is_array($newKey) ? $newKey : null,
        ], 'layouts/admin');
    }

    public function createKey(): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $name = post('name');

        if ($name === '') {
            Flash::error(t('Bitte einen Namen für den Schlüssel angeben (wofür wird er verwendet?).'));
            Url::redirect('/admin/api');
        }

        $gym   = post_id('gym_id') !== null ? GymRepo::find((int) post_id('gym_id')) : null;
        $event = post_id('event_id') !== null ? EventRepo::find((int) post_id('event_id')) : null;

        $created = ApiKeyRepo::create(
            $name,
            post('scope'),
            $gym !== null ? (int) $gym['id'] : null,
            $event !== null ? (int) $event['id'] : null,
            Auth::id()
        );

        $_SESSION['new_api_key'] = ['name' => $name, 'key' => $created['key']];

        Audit::log('api_key_created', 'api_key', $created['id'], $name);
        Url::redirect('/admin/api');
    }

    public function toggleKey(array $args): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $row = Database::one('SELECT * FROM api_keys WHERE id = ?', [(int) ($args['id'] ?? 0)]);

        if ($row !== null) {
            Database::update('api_keys', (int) $row['id'], ['active' => (int) $row['active'] === 1 ? 0 : 1]);
            Audit::log('api_key_toggled', 'api_key', (int) $row['id'], (string) $row['name']);
            Flash::success((int) $row['active'] === 1
                ? t('Schlüssel „%s“ deaktiviert.', $row['name'])
                : t('Schlüssel „%s“ aktiviert.', $row['name']));
        }

        Url::redirect('/admin/api');
    }

    public function deleteKey(array $args): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $row = Database::one('SELECT * FROM api_keys WHERE id = ?', [(int) ($args['id'] ?? 0)]);

        if ($row !== null) {
            Database::run('DELETE FROM api_keys WHERE id = ?', [(int) $row['id']]);
            Audit::log('api_key_deleted', 'api_key', (int) $row['id'], (string) $row['name']);
            Flash::success(t('Schlüssel gelöscht – gekoppelte Systeme mit diesem Schlüssel haben keinen Zugriff mehr.'));
        }

        Url::redirect('/admin/api');
    }

    // ------------------------------------------------------------- Webhooks --

    public function createWebhook(): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $url = post('url');

        if (!preg_match('#^https?://#i', $url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            Flash::error(t('Bitte eine vollständige Adresse angeben (https://…).'));
            Url::redirect('/admin/api');
        }

        $event = post_id('event_id') !== null ? EventRepo::find((int) post_id('event_id')) : null;

        $id = Database::insert('webhooks', [
            'event_id' => $event !== null ? (int) $event['id'] : null,
            'name'     => post('name'),
            'url'      => $url,
            'secret'   => bin2hex(random_bytes(20)),
        ]);

        Audit::log('webhook_created', 'webhook', $id, $url);
        Flash::success(t('Webhook angelegt. Das Secret steht in der Liste – beim Empfänger eintragen.'));
        Url::redirect('/admin/api');
    }

    public function testWebhook(array $args): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $hook = Database::one('SELECT * FROM webhooks WHERE id = ?', [(int) ($args['id'] ?? 0)]);

        if ($hook !== null) {
            $slug   = $hook['event_id'] !== null ? (string) Database::value('SELECT slug FROM events WHERE id = ?', [(int) $hook['event_id']]) : '';
            $status = Webhook::deliver($hook, ['event' => $slug, 'event_id' => $hook['event_id'], 'type' => 'ping', 'at' => gmdate('c')]);
            Flash::info(t('Test gesendet: %s', $status));
        }

        Url::redirect('/admin/api');
    }

    public function deleteWebhook(array $args): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        Database::run('DELETE FROM webhooks WHERE id = ?', [(int) ($args['id'] ?? 0)]);
        Flash::success(t('Webhook gelöscht.'));
        Url::redirect('/admin/api');
    }
}
