<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Code Execution Settings
    |--------------------------------------------------------------------------
    */
    'execution' => [
        'timeout' => env('MCP_EXECUTION_TIMEOUT', 30),
        'memory_limit' => env('MCP_MEMORY_LIMIT', '128M'),
        'sandbox_enabled' => env('MCP_SANDBOX_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('MCP_LOGGING_ENABLED', true),
        'retention_days' => env('MCP_LOG_RETENTION_DAYS', 30),
        'log_request_body' => env('MCP_LOG_REQUEST_BODY', true),
        'log_response_body' => env('MCP_LOG_RESPONSE_BODY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'web' => ['throttle:60,1'],
        'local' => [],
    ],
];
