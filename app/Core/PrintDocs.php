<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\BoutRepo;
use App\Models\EventRepo;

/**
 * Druckansichten eines Turniers, die Verwaltung und Website gemeinsam nutzen:
 * Turnierbaeume als "Spinne" (quer, eine Kategorie je Seite) und die Running
 * Order (Kampfreihenfolge je Wettkampfstaette). Gedruckt bzw. als PDF
 * gespeichert wird ueber den Druckdialog des Browsers.
 */
final class PrintDocs
{
    public const DOCS = ['spinne', 'running-order'];

    /** Druckansicht ausgeben; Filter kommen aus der Adresse (?kat=, CategoryFilter, ?staette=, ?tag=). */
    public static function display(array $event, string $doc): void
    {
        $eventId = (int) $event['id'];
        $bouts   = BoutRepo::forEvent($eventId);
        $data    = [
            'title'   => $event['name'],
            'event'   => $event,
            'times'   => Timetable::compute($event, $bouts),
            'feeders' => self::feeders($bouts),
        ];

        if ($doc === 'spinne') {
            $kat = query('kat') !== '' ? (int) query('kat') : null;

            View::display('admin/events/print-spinne', $data + [
                'title'     => $event['name'] . ' – ' . t('Turnierbäume'),
                'brackets'  => self::brackets($eventId, $kat, CategoryFilter::fromQuery()),
                'landscape' => true,
            ], 'layouts/print');

            return;
        }

        $staette = query('staette') !== '' ? (int) query('staette') : null;
        $tag     = query('tag') !== '' ? (int) query('tag') : null;

        View::display('admin/events/print-running-order', $data + [
            'title' => $event['name'] . ' – Running Order',
            'days'  => self::runningOrder($eventId, $staette, $tag),
        ], 'layouts/print');
    }

    /**
     * Turnierbaeume der Kategorien mit Kaempfen.
     *
     * @return list<array{category:array<string,mixed>,rounds:list<array{round:int,label:string,bouts:list<array<string,mixed>>}>}>
     */
    public static function brackets(int $eventId, ?int $categoryId = null, array $filter = []): array
    {
        $out        = [];
        $categories = array_values(array_filter(EventRepo::categories($eventId), static fn (array $c): bool => (int) $c['bout_count'] > 0));

        if ($filter !== []) {
            $categories = CategoryFilter::apply($eventId, $categories, $filter);
        }

        foreach ($categories as $c) {
            if ($categoryId !== null && (int) $c['id'] !== $categoryId) {
                continue;
            }

            $rounds = Bracket::rounds((int) $c['id']);

            if ($rounds !== []) {
                $out[] = ['category' => $c, 'rounds' => $rounds];
            }
        }

        return $out;
    }

    /**
     * Wer kommt in eine noch offene Ecke? Kampf-Id => ['red' => Kampfnummer,
     * 'blue' => Kampfnummer] der Zubringer, deren Sieger noch nicht feststeht.
     *
     * @param list<array<string,mixed>> $bouts
     * @return array<int,array<string,int>>
     */
    public static function feeders(array $bouts): array
    {
        $out = [];

        foreach ($bouts as $b) {
            if ($b['next_bout_id'] === null || (string) $b['next_slot'] === '' || !BoutRepo::needsPlacement($b)) {
                continue;
            }

            $out[(int) $b['next_bout_id']][(string) $b['next_slot']] = (int) $b['bout_no'];
        }

        return $out;
    }

    /**
     * Zeitplan fuer die Running Order: Tage -> Abschnitte -> Wettkampfstaetten,
     * jeweils nur mit eingeplanten Kaempfen; leere Staetten/Abschnitte entfallen.
     *
     * @return list<array<string,mixed>>
     */
    public static function runningOrder(int $eventId, ?int $venueId = null, ?int $dayId = null): array
    {
        $days = [];

        foreach (BoutRepo::schedule($eventId)['days'] as $day) {
            if ($dayId !== null && (int) $day['id'] !== $dayId) {
                continue;
            }

            $sessions = [];

            foreach ($day['sessions'] as $session) {
                $venues = [];

                foreach ($session['venues'] as $vid => $venue) {
                    if ($venueId !== null && (int) $vid !== $venueId) {
                        continue;
                    }

                    $venue['bouts'] = array_values(array_filter(
                        $venue['bouts'],
                        static fn (array $b): bool => (int) ($b['active'] ?? 1) === 1 && ((int) $b['is_break'] === 1 || BoutRepo::needsPlacement($b))
                    ));

                    if ($venue['bouts'] !== []) {
                        $venues[] = $venue;
                    }
                }

                if ($venues !== []) {
                    $sessions[] = ['venues' => $venues] + $session;
                }
            }

            if ($sessions !== []) {
                $days[] = ['sessions' => $sessions] + $day;
            }
        }

        return $days;
    }
}
