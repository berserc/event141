<?php

/**
 * Konfigurations-Vorlage fuer verwaltete Installationen (DevWorld Cloud).
 * Der Provisioner ersetzt die {{platzhalter}} und legt das Ergebnis als
 * app/config.php ab (steht in der preserve-Liste von product.json).
 */

return [
    'env' => 'live',
    'app_name' => '{{app_name}}',
    'base_path' => '',
    'db_path' => dirname(__DIR__) . '/data/event141.sqlite',
    'upload_dir' => dirname(__DIR__) . '/public/uploads',
    'upload_url' => '/uploads',
    'timezone' => 'Europe/Vienna',
    'debug' => false,
    'noindex' => false,
    'show_env_banner' => false,
    'session_name' => 'event141_sess',
    'canonical_host' => '{{primary_host}}',
    'login_max_attempts' => 10,
    'country_code' => '{{country_code}}',
    'setup_key' => '',
    'gym141_timeout' => 15,

    // Cloud-Tenants zahlen fuers Hosting – das Gratis-Limit (1 aktives Event) gilt hier nicht.
    'free_event_limit' => 100000,
];
