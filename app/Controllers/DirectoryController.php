<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Directory;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;

/**
 * Zentrales Event-Verzeichnis: Meldungen der Instanzen annehmen (API) und die
 * Eintraege in der Verwaltung freigeben, verstecken, aktualisieren, entfernen.
 * Nur aktiv, wenn die Installation als Verzeichnis laeuft ('directory' => true).
 */
final class DirectoryController
{
    /** POST /api/directory/ping  {"url":"https://instanz","slug":"event-kuerzel"} */
    public function ping(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (!Directory::isDirectory()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Diese Installation ist kein Verzeichnis.'], JSON_UNESCAPED_UNICODE);

            return;
        }

        $body = json_decode((string) file_get_contents('php://input'), true);
        $body = is_array($body) ? $body : $_POST;

        $result = Directory::ping((string) ($body['url'] ?? ''), (string) ($body['slug'] ?? ''));

        http_response_code($result['ok'] ? 200 : 422);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function index(): void
    {
        AuthController::requireRole('superuser');
        $this->requireDirectory();

        View::display('admin/directory', [
            'title' => t('Verzeichnis'),
            'rows'  => Database::all('SELECT * FROM directory_events ORDER BY (state = \'wartet\') DESC, starts_on DESC, name'),
        ], 'layouts/admin');
    }

    /** Eintrag freigeben, verstecken, neu abholen oder entfernen. */
    public function action(array $args): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();
        $this->requireDirectory();

        $row = Database::one('SELECT * FROM directory_events WHERE id = ?', [(int) ($args['id'] ?? 0)]);

        if ($row === null) {
            Flash::error(t('Eintrag nicht gefunden.'));
            Url::redirect('/admin/verzeichnis');
        }

        $aktion = post('aktion');

        if ($aktion === 'freigeben' || $aktion === 'verstecken') {
            Database::update('directory_events', (int) $row['id'], ['state' => $aktion === 'freigeben' ? 'sichtbar' : 'versteckt']);
            Flash::success($aktion === 'freigeben' ? t('„%s“ ist jetzt im Verzeichnis sichtbar.', $row['name']) : t('„%s“ ist versteckt.', $row['name']));
        } elseif ($aktion === 'host-freigeben') {
            // Alle wartenden Eintraege dieser Instanz auf einmal freigeben.
            $n = Database::run("UPDATE directory_events SET state = 'sichtbar' WHERE host = ? AND state = 'wartet'", [(string) $row['host']])->rowCount();
            Flash::success(t('%1$d Einträge von %2$s freigegeben.', $n, $row['host']));
        } elseif ($aktion === 'abholen') {
            $r = Directory::ping((string) $row['source_url'], (string) $row['slug']);
            $r['ok']
                ? Flash::success(t('Neu abgeholt: %s', t((string) ($r['state'] ?? ''))))
                : Flash::error(t('Abholen fehlgeschlagen: %s', (string) ($r['error'] ?? '')));
        } elseif ($aktion === 'entfernen') {
            Database::run('DELETE FROM directory_events WHERE id = ?', [(int) $row['id']]);
            Flash::success(t('„%s“ aus dem Verzeichnis entfernt.', $row['name']));
        }

        Audit::log('directory_' . ($aktion !== '' ? $aktion : 'aktion'), 'directory', (int) $row['id'], $row['host'] . '/' . $row['slug']);
        Url::redirect('/admin/verzeichnis');
    }

    /** Alle Eintraege neu abholen. */
    public function refresh(): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();
        $this->requireDirectory();

        $r = Directory::refreshAll();
        Flash::success(t('Verzeichnis aktualisiert: %1$d abgeholt, %2$d entfernt, %3$d nicht erreichbar.', $r['ok'], $r['entfernt'], $r['fehler']));
        Url::redirect('/admin/verzeichnis');
    }

    private function requireDirectory(): void
    {
        if (!Directory::isDirectory()) {
            Flash::error(t('Diese Installation ist kein Verzeichnis.'));
            Url::redirect('/admin');
        }
    }
}
