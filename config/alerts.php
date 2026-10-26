<?php

/*
 * Founder alerts (AD-20): failed jobs from the app; failed and missing
 * backups from the heartbeat monitor (docs/operations/alerts.md).
 */
return [

    // Where alerts go. Unset means every alert is logged as undeliverable.
    'founder_email' => env('FOUNDER_ALERT_EMAIL'),

    // The mailer that carries them (an SMTP provider in production); null is the default mailer.
    'mailer' => env('ALERTS_MAILER'),

    // A burst of the same kind sends one alert per this many minutes.
    'throttle_minutes' => 15,

    // Throttle state lives on the database cache, never on the queue it reports on.
    'cache_store' => env('ALERTS_CACHE_STORE', 'database'),

    // Times are shown in WAT and UTC.
    'local_timezone' => 'Africa/Lagos',
    'local_timezone_label' => 'WAT',

];
