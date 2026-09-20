<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Models\EventRepo;
use App\Models\GymRepo;

final class DashboardController
{
    public function index(): void
    {
        AuthController::requireLogin();

        $events = EventRepo::all();

        $kommend = array_values(array_filter(
            $events,
            static fn (array $e): bool => (string) ($e['ends_on'] ?: $e['starts_on']) >= date('Y-m-d')
        ));

        $laufend = array_values(array_filter($events, static fn (array $e): bool => $e['status'] === 'laufend'));

        View::display('admin/dashboard', [
            'title'       => 'Übersicht',
            'upcoming'    => array_slice(array_reverse($kommend), 0, 8),
            'running'     => $laufend,
            'stats'       => [
                'events'   => count($events),
                'gyms'     => (int) Database::value('SELECT COUNT(*) FROM gyms WHERE deleted_at IS NULL'),
                'athletes' => (int) Database::value('SELECT COUNT(*) FROM athletes WHERE active = 1'),
                'entries'  => (int) Database::value("SELECT COUNT(*) FROM event_entries WHERE status = 'angemeldet'"),
                'gymsNew'  => GymRepo::pendingCount(),
            ],
            'recentGyms'  => Database::all(
                "SELECT * FROM gyms WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 6"
            ),
            'recentEntries' => Database::all(
                "SELECT x.created_at, x.status, a.first_name, a.last_name, g.name AS gym_name,
                        e.name AS event_name, e.id AS event_id, c.name AS category_name
                   FROM event_entries x
                   JOIN athletes a ON a.id = x.athlete_id
                   JOIN gyms g ON g.id = x.gym_id
                   JOIN events e ON e.id = x.event_id
                   LEFT JOIN event_categories c ON c.id = x.category_id
                  ORDER BY x.created_at DESC LIMIT 10"
            ),
        ], 'layouts/admin');
    }
}
