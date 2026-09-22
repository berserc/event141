<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Updater;
use App\Core\Url;
use App\Core\View;
use RuntimeException;
use Throwable;

/** Ein-Klick-Updates (nur Superuser). */
final class SystemController
{
    public function updates(): void
    {
        AuthController::requireRole('superuser');

        $manifest = null;

        // Manifest nur auf Wunsch laden, damit die Seite ohne Netz schnell bleibt.
        if (query('pruefen') === '1') {
            $manifest = Updater::manifest();

            if ($manifest === null) {
                Flash::error('Update-Server nicht erreichbar (' . Updater::manifestUrl() . ').');
            }
        }

        View::display('admin/system/updates', [
            'title'    => 'Updates',
            'version'  => Updater::currentVersion(),
            'manifest' => $manifest,
            'geprueft' => query('pruefen') === '1',
            'log'      => $_SESSION['_update_log'] ?? [],
        ], 'layouts/admin');

        unset($_SESSION['_update_log']);
    }

    public function installUpdate(): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $manifest = Updater::manifest();

        if ($manifest === null) {
            Flash::error('Update-Server nicht erreichbar.');
            Url::redirect('/admin/updates');
        }

        if (!Updater::updateAvailable($manifest)) {
            Flash::info('Es ist bereits die neueste Version installiert.');
            Url::redirect('/admin/updates');
        }

        try {
            $log = Updater::apply($manifest);

            $_SESSION['_update_log'] = $log;
            Audit::log('system_updated', 'system', null, 'Version ' . $manifest['version']);
            Flash::success('Update auf Version ' . $manifest['version'] . ' installiert.');
        } catch (RuntimeException $e) {
            Flash::error('Update abgebrochen: ' . $e->getMessage());
        } catch (Throwable $e) {
            error_log('[event141] Update-Fehler: ' . $e->getMessage());
            Flash::error('Unerwarteter Fehler beim Update – Details im Server-Log. '
                . 'Eine Datenbanksicherung liegt unter data/backups/.');
        }

        Url::redirect('/admin/updates');
    }
}
