<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Voraussichtliche Beginnzeiten der Kaempfe.
 *
 * Je Abschnitt und Wettkampfstaette laeuft eine eigene Uhr: Start ist die
 * Beginnzeit des Abschnitts (sonst die des Events), danach zaehlt die Dauer
 * jedes Kampfs bzw. jeder Pause (minutes, 0 = Standard des Events). Eine fest
 * eingetragene Uhrzeit (scheduled_time) setzt die Uhr neu.
 *
 * Im Live-Modus zaehlen die echten Start-/Endzeiten (started_at/finished_at),
 * und ein laufender Kampf, der ueberzieht, schiebt alles Folgende nach hinten.
 */
final class Timetable
{
    /**
     * @param array<string,mixed>       $event
     * @param list<array<string,mixed>> $bouts chronologisch (BoutRepo::forEvent)
     * @return array<int,array{start:int,end:int,minutes:int,label:string}> je Kampf-ID
     */
    public static function compute(array $event, array $bouts, ?int $now = null): array
    {
        $now  = $now ?? time();
        $live = (int) ($event['live_mode'] ?? 0) === 1;
        $out  = [];
        $clock = []; // "session:venue" => Unix-Zeit

        foreach ($bouts as $b) {
            if ((int) ($b['active'] ?? 1) !== 1 || (string) $b['status'] === 'abgesagt') {
                continue;
            }

            if ((string) $b['status'] === 'beendet' && (string) $b['method'] === 'Freilos') {
                continue;
            }

            $date = (string) ($b['day_date'] ?? '') ?: (string) $event['starts_on'];
            $key  = (int) ($b['session_id'] ?? 0) . ':' . (int) ($b['venue_id'] ?? 0);

            if (!isset($clock[$key])) {
                $startTime   = (string) ($b['session_start'] ?? '') ?: ((string) ($event['start_time'] ?? '') ?: '00:00');
                $clock[$key] = self::ts($date, $startTime);
            }

            if ((string) $b['scheduled_time'] !== '' && !$live) {
                $clock[$key] = self::ts($date, (string) $b['scheduled_time']);
            }

            $minutes = self::minutes($event, $b);
            $status  = (string) $b['status'];
            $start   = $clock[$key];

            if ($live && in_array($status, ['laufend', 'beendet'], true) && !empty($b['started_at'])) {
                $start = (int) strtotime($b['started_at'] . ' UTC');
            }

            $end = $start + $minutes * 60;

            if ($live && $status === 'beendet' && !empty($b['finished_at'])) {
                $end = max($start, (int) strtotime($b['finished_at'] . ' UTC'));
            }

            if ($live && $status === 'laufend' && $now > $end) {
                $end = $now;
            }

            $clock[$key] = $end;

            $hhmm = date('H:i', $start);

            $out[(int) $b['id']] = [
                'start'   => $start,
                'end'     => $end,
                'minutes' => $minutes,
                'label'   => $status === 'beendet' ? $hhmm : ($status === 'laufend' ? t('seit %s', $hhmm) : t('ca. %s', $hhmm)),
            ];
        }

        return $out;
    }

    /** Dauer eines Eintrags in Minuten. */
    public static function minutes(array $event, array $bout): int
    {
        $n = (int) ($bout['minutes'] ?? 0);

        if ($n > 0) {
            return $n;
        }

        if ((int) $bout['is_break'] === 1) {
            if (preg_match('/(\d+)/', (string) $bout['note'], $m)) {
                return max(1, (int) $m[1]);
            }

            return max(1, (int) ($event['default_break_minutes'] ?? 15));
        }

        return max(1, (int) ($event['default_bout_minutes'] ?? 12));
    }

    /** Unix-Zeit des Eventbeginns (Countdown). */
    public static function eventStart(array $event): int
    {
        return self::ts((string) $event['starts_on'], (string) ($event['start_time'] ?? '') ?: '00:00');
    }

    private static function ts(string $date, string $time): int
    {
        $ts = strtotime($date . ' ' . $time);

        return $ts === false ? time() : $ts;
    }
}
