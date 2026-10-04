<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Flash;
use App\Core\Ruleset;
use App\Core\Url;
use App\Core\View;

/** Regelsaetze der Verbaende zum Nachschlagen (Alters-/Gewichtsklassen, Kampfzeiten). */
final class RulesetController
{
    public function index(): void
    {
        AuthController::requireLogin();

        View::display('admin/rulesets/index', [
            'title'    => t('Regelsätze'),
            'rulesets' => Ruleset::all(),
        ], 'layouts/admin');
    }

    public function show(array $args): void
    {
        AuthController::requireLogin();

        $ruleset = Ruleset::find((string) ($args['code'] ?? ''));

        if ($ruleset === null) {
            Flash::error(t('Regelsatz nicht gefunden.'));
            Url::redirect('/admin/regelsaetze');
        }

        View::display('admin/rulesets/show', [
            'title'   => t((string) $ruleset['name']),
            'ruleset' => $ruleset,
        ], 'layouts/admin');
    }
}
