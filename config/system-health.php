<?php

return [
    'scheduler_cache_key' => 'citirx:system-health:scheduler-heartbeat',

    'scheduler_stale_minutes' => (int) env(
        'SYSTEM_HEALTH_SCHEDULER_STALE_MINUTES',
        5,
    ),

    'backup_stale_hours' => (int) env(
        'SYSTEM_HEALTH_BACKUP_STALE_HOURS',
        25,
    ),

    /*
     * Populate these at deployment time. They are deliberately
     * allow-listed and validated before being displayed.
     */
    'application_version' => env('APP_VERSION'),
    'application_commit' => env('APP_COMMIT'),
];
