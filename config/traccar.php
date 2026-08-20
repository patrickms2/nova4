<?php

declare(strict_types=1);

$apiBaseUrl = (string) env('TRACCAR_API_URL', env('TRACCAR_BASE_URL', 'http://api.taxisnorteysur.com:8082/api'));
$clientBaseUrl = (string) env('TRACCAR_CLIENT_URL', '');

if ($clientBaseUrl === '') {
    $parsedApiUrl = parse_url($apiBaseUrl);

    if (is_array($parsedApiUrl) && isset($parsedApiUrl['scheme'], $parsedApiUrl['host'])) {
        $clientBaseUrl = sprintf(
            '%s://%s:%s',
            $parsedApiUrl['scheme'],
            $parsedApiUrl['host'],
            (string) env('TRACCAR_CLIENT_PORT', '5055'),
        );
    } else {
        $clientBaseUrl = 'http://api.taxisnorteysur.com:5055';
    }
}

return [
    /*
    |--------------------------------------------------------------------------
    | Traccar Base URL
    |--------------------------------------------------------------------------
    | The base URL of your Traccar server’s API endpoint.
    | Example: https://your-traccar-server.com/api
    */
    'base_url' => env(key: 'TRACCAR_BASE_URL'),

    /*
     |--------------------------------------------------------------------------
     | Traccar API Key
     |--------------------------------------------------------------------------
     | The API key used to authenticate requests to the Traccar server.
     | You can obtain this from your Traccar server administrator or
     | configuration settings.
     */
    'api_key' => env(key: 'TRACCAR_API_KEY'),

    'url' => env('TRACCAR_BASE_URL', 'http://api.taxisnorteysur.com:8082/api'),

    'client_url' => rtrim($clientBaseUrl, '/'),

    /**
     * Authentication method: 'basic' or 'token'
     * - basic: Uses username/password (TRACCAR_USERNAME, TRACCAR_PASSWORD)
     * - token: Uses API token (TRACCAR_TOKEN)
     */
    'auth_method' => env('TRACCAR_AUTH_METHOD', 'basic'),

    /**
     * Basic authentication credentials (if auth_method = 'basic')
     */
    'username' => env('TRACCAR_USERNAME', 'admin'),
    'password' => env('TRACCAR_PASSWORD', 'admin'),

    /**
     * API token for authentication (if auth_method = 'token')
     * Token should be generated from Traccar server settings
     */
    'token' => env('TRACCAR_TOKEN', ''),

    /**
     * Webhook authentication token
     * Used to verify incoming webhook requests from Traccar
     * Generate a secure random string for production
     */
    'webhook_token' => env('TRACCAR_WEBHOOK_TOKEN', ''),

    /**
     * API endpoints
     */
    'endpoints' => [
        'positions' => '/api/positions',
        'devices' => '/api/devices',
        'reports_route' => '/api/reports/route',
    ],

    /**
     * Sync settings
     */
    'sync' => [
        /**
         * Default time window for fetching positions (in hours)
         * Used when no specific time range is provided
         */
        'default_window_hours' => env('TRACCAR_SYNC_WINDOW_HOURS', 24),

        /**
         * Maximum number of positions to fetch per request
         */
        'max_positions_per_request' => env('TRACCAR_MAX_POSITIONS', 1000),

        /**
         * Timeout for HTTP requests (in seconds)
         */
        'timeout' => env('TRACCAR_TIMEOUT', 30),

        /**
         * Only sync positions for trips with these statuses
         */
        'trip_statuses' => ['ongoing'],
    ],

    /**
     * Enable/disable features
     */
    'features' => [
        /**
         * Enable automatic position syncing
         */
        'auto_sync' => env('TRACCAR_AUTO_SYNC', true),

        /**
         * Enable webhook endpoint for real-time position updates
         */
        'webhook_enabled' => env('TRACCAR_WEBHOOK_ENABLED', true),

        /**
         * Log all Traccar API requests/responses for debugging
         */
        'debug_logging' => env('TRACCAR_DEBUG_LOGGING', false),
    ],

];
