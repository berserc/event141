<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\BoutRepo;
use App\Models\EntryRepo;

/**
 * Turnierbaum (K.-o.-System, Einfachausscheidung).
 *
 * Aus den bestaetigten Anmeldungen einer Kategorie entsteht ein Baum mit
 * 2^n Plaetzen. Gesetzte Sportler (seed 1, 2, …) werden nach dem ueblichen
 * Setzschema verteilt (1 gegen 16, 8 gegen 9 …); fehlende Plaetze sind
 * Freilose, deren Gegner automatisch eine Runde weiterkommen.
 */
final class Bracket
{
    /**
     * Erzeugt den Baum neu. Bestehende Kaempfe der Kategorie werden ersetzt –
     * bereits eingetragene Ergebnisse gehen dabei verloren.
     *
     * @return array{bouts:int,rounds:int,entries:int,byes:int}
     */
    public static function generate(array $event, array $category): array
    {
        $eventId    = (int) $event['id'];
        $categoryId = (int) $category['id'];
        $entries    = EntryRepo::confirmedForCategory($categoryId);
        $n          = count($entries);

        Database::run('DELETE FROM event_bouts WHERE category_id = ?', [$categoryId]);

        if ($n < 2) {
            return ['bouts' => 0, 'rounds' => 0, 'entries' => $n, 'byes' => 0];
        }

        $size   = 2;
        $rounds = 1;

        while ($size < $n) {
            $size  *= 2;
            $rounds++;
        }

        // Setzplaetze -> Baumpositionen (Standard-Setzschema)
        $order = self::seedOrder($size);
        $slots = array_fill(0, $size, null);

        foreach ($order as $position => $seed) {
            $slots[$position] = $entries[$seed - 1] ?? null;
        }

        $boutNo  = BoutRepo::nextBoutNo($eventId);
        $rundenIds = [];   // round => list<bout id> in bracket_pos-Reihenfolge
        $created = 0;

        Database::transaction(static function () use (
            $eventId, $categoryId, $category, $size, $rounds, $slots, &$boutNo, &$rundenIds, &$created
        ): void {
            // Alle Runden von hinten anlegen, damit next_bout_id gesetzt werden kann.
            for ($round = $rounds; $round >= 1; $round--) {
                $anzahl = (int) ($size / (2 ** $round));
                $rundenIds[$round] = [];

                for ($pos = 0; $pos < $anzahl; $pos++) {
                    $nextId   = null;
                    $nextSlot = '';

                    if ($round < $rounds) {
                        $nextId   = $rundenIds[$round + 1][intdiv($pos, 2)];
                        $nextSlot = $pos % 2 === 0 ? 'red' : 'blue';
                    }

                    $red  = null;
                    $blue = null;

                    if ($round === 1) {
                        $red  = $slots[$pos * 2];
                        $blue = $slots[$pos * 2 + 1];
                    }

                    $id = Database::insert('event_bouts', [
                        'event_id'      => $eventId,
                        'category_id'   => $categoryId,
                        'bout_no'       => 0,
                        'round_no'      => $round,
                        'round_label'   => BoutRepo::roundLabel($round, $rounds),
                        'bracket_pos'   => $pos,
                        'red_entry_id'  => $red !== null ? (int) $red['id'] : null,
                        'blue_entry_id' => $blue !== null ? (int) $blue['id'] : null,
                        'next_bout_id'  => $nextId,
                        'next_slot'     => $nextSlot,
                        'rounds'        => (int) $category['rounds'],
                        'round_minutes' => (float) $category['round_minutes'],
                    ]);

                    $rundenIds[$round][$pos] = $id;
                    $created++;
                }
            }

            // Kampfnummern in Runden-Reihenfolge vergeben (Runde 1 zuerst).
            for ($round = 1; $round <= $rounds; $round++) {
                foreach ($rundenIds[$round] as $id) {
                    Database::update('event_bouts', $id, ['bout_no' => $boutNo++]);
                }
            }

            // Freilose: Kampf mit nur einem Sportler ist sofort entschieden.
            foreach ($rundenIds[1] as $id) {
                $bout = Database::one('SELECT * FROM event_bouts WHERE id = ?', [$id]);

                if ($bout === null) {
                    continue;
                }

                $hatRot  = $bout['red_entry_id'] !== null;
                $hatBlau = $bout['blue_entry_id'] !== null;

                if ($hatRot && $hatBlau) {
                    continue;
                }

                if (!$hatRot && !$hatBlau) {
                    Database::update('event_bouts', $id, ['status' => 'abgesagt', 'winner' => 'none', 'method' => 'Freilos']);
                    continue;
                }

                BoutRepo::setResult($id, $hatRot ? 'red' : 'blue', 'Freilos', '');
            }
        });

        // Zweite Runde: treffen zwei Freilos-Sieger aufeinander? Nein – aber
        // trifft ein Freilos-Sieger auf ein leeres Feld (bei sehr wenigen
        // Teilnehmern), kommt er ebenfalls kampflos weiter.
        self::resolveEmptyRounds($rundenIds, $rounds);

        return ['bouts' => $created, 'rounds' => $rounds, 'entries' => $n, 'byes' => $size - $n];
    }

    /**
     * Standard-Setzreihenfolge fuer $size Plaetze: Position => Setznummer.
     * 4 Plaetze: [1, 4, 2, 3]  (1 vs 4, 2 vs 3)
     * 8 Plaetze: [1, 8, 4, 5, 2, 7, 3, 6]
     *
     * @return list<int>
     */
    public static function seedOrder(int $size): array
    {
        $order = [1];

        while (count($order) < $size) {
            $next = [];
            $sum  = count($order) * 2 + 1;

            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $sum - $seed;
            }

            $order = $next;
        }

        return $order;
    }

    /**
     * Kaempfe hoeherer Runden, die nach den Freilosen nur einen Sportler
     * haben UND deren zweiter Zubringer abgesagt ist, kampflos entscheiden.
     *
     * @param array<int,array<int,int>> $rundenIds
     */
    private static function resolveEmptyRounds(array $rundenIds, int $rounds): void
    {
        for ($round = 2; $round <= $rounds; $round++) {
            foreach ($rundenIds[$round] as $pos => $id) {
                $bout = Database::one('SELECT * FROM event_bouts WHERE id = ?', [$id]);

                if ($bout === null || (string) $bout['status'] === 'beendet') {
                    continue;
                }

                $zubringer = Database::all(
                    'SELECT status FROM event_bouts WHERE next_bout_id = ?',
                    [$id]
                );

                $abgesagt = count(array_filter($zubringer, static fn (array $z): bool => $z['status'] === 'abgesagt'));

                if ($abgesagt === 0) {
                    continue;
                }

                $hatRot  = $bout['red_entry_id'] !== null;
                $hatBlau = $bout['blue_entry_id'] !== null;

                if ($hatRot xor $hatBlau) {
                    BoutRepo::setResult($id, $hatRot ? 'red' : 'blue', 'Freilos', '');
                } elseif (!$hatRot && !$hatBlau && $abgesagt === 2) {
                    Database::update('event_bouts', $id, ['status' => 'abgesagt', 'winner' => 'none', 'method' => 'Freilos']);
                }
            }
        }
    }

    /**
     * Baum fuer die Anzeige: Runden mit Kaempfen in Positionsreihenfolge.
     *
     * @return list<array{round:int,label:string,bouts:list<array<string,mixed>>}>
     */
    public static function rounds(int $categoryId): array
    {
        $out = [];

        foreach (BoutRepo::forCategory($categoryId) as $bout) {
            $r = (int) $bout['round_no'];

            if (!isset($out[$r])) {
                $out[$r] = ['round' => $r, 'label' => (string) $bout['round_label'], 'bouts' => []];
            }

            $out[$r]['bouts'][] = $bout;
        }

        ksort($out);

        return array_values($out);
    }
}
