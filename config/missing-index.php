<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Missing Index Detection Enabled
    |--------------------------------------------------------------------------
    |
    | This option may be used to enable/disable query analysis for missing
    | indexes. When set to null, it will use the value of app.debug.
    |
    */
    'enabled' => env('MISSING_INDEX_ENABLED', null),

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    |
    | The package will only run in these environments. Leave empty to run
    | in all environments (not recommended for production).
    |
    */
    'environments' => ['local', 'testing'],

    /*
    |--------------------------------------------------------------------------
    | Execution Time Threshold
    |--------------------------------------------------------------------------
    |
    | Only analyze queries that take longer than this threshold (in milliseconds).
    | Set to 0 to analyze all queries.
    |
    */
    'threshold_ms' => (int) env('MISSING_INDEX_THRESHOLD_MS', 0),

    /*
    |--------------------------------------------------------------------------
    | Ignored Tables
    |--------------------------------------------------------------------------
    |
    | Tables to ignore when analyzing queries. Typically includes framework
    | tables like migrations, sessions, cache, etc.
    |
    */
    'ignore_tables' => [
        'migrations',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'failed_jobs',
        'password_resets',
        'password_reset_tokens',
        'personal_access_tokens',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Patterns
    |--------------------------------------------------------------------------
    |
    | Regex patterns to ignore. Queries matching these patterns will be
    | skipped during analysis.
    |
    */
    'ignore_patterns' => [
        // Example: '/^SELECT \* FROM information_schema/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Handlers
    |--------------------------------------------------------------------------
    |
    | Classes that handle the output of detected missing indexes.
    | Must implement OeleDev\MissingIndex\Outputs\Output interface.
    |
    */
    'output' => [
        \OeleDev\MissingIndex\Outputs\Log::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | The log channel to use when logging detected missing indexes.
    |
    */
    'log_channel' => env('MISSING_INDEX_LOG_CHANNEL', 'daily'),
];
