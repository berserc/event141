<?php

declare(strict_types=1);

namespace App\Core;

/** Grunddaten fuer eine frische Installation. */
final class SeedData
{
    /** @return array<string,string> */
    public static function settings(): array
    {
        return [
            'org_name'      => (string) Config::get('app_name', 'Event141'),
            'org_tagline'   => '',
            'org_street'    => '',
            'org_zip'       => '',
            'org_city'      => '',
            'org_email'     => '',
            'org_phone'     => '',
            'org_website'   => '',
            'home_title'    => 'Unsere Events',
            'home_text'     => '',
            // '' = Startseite listet alle Events; Slug = diese Event-Seite ist die Startseite
            'home_event'    => '',
            'public_site'   => '1',
            'gym_area'      => '1',
            'gym_signup'    => '1',
            // Standard-Siegarten fuer die Ergebniseingabe (eine je Zeile)
            'win_methods'   => "Punkte\nKO\nTKO\nAufgabe\nDisqualifikation\nVerletzung\nWalkover",
        ];
    }
}
