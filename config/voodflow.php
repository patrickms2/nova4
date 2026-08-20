<?php

use Voodflow\Voodflow\Models\Edge;
use Voodflow\Voodflow\Models\Execution;
use Voodflow\Voodflow\Models\ExecutionNode;
use Voodflow\Voodflow\Models\Node;
use Voodflow\Voodflow\Models\Workflow;

return [
    'auto_discover_events' => env('VOODFLOW_AUTO_DISCOVER_EVENTS', false),

    /*
     * Frontend debug logging.
     *
     * When enabled, Voodflow's JS bundle will emit verbose console logs useful
     * for troubleshooting (Echo/Reverb, live node updates, dynamic loader, etc).
     *
     * In production you typically want this disabled.
     */
    'frontend_debug' => env('VOODFLOW_FRONTEND_DEBUG', false),

    /*
     * api.voodflow.com — version + license checks (URL is fixed in VoodflowApiEndpoints, not .env).
     * License key is read from Composer (auth.json / COMPOSER_AUTH), same password used for
     * the Anystack repository. Fingerprint sent to the portal is APP_URL host unless overridden below.
     */
    'portal' => [
        'cache_ttl_hours' => (int) env('VOODFLOW_PORTAL_CACHE_HOURS', 24),
        /*
         * License status (dashboard): separate, shorter TTL than the release tag cache so
         * an Anystack deactivation shows up within minutes instead of after 24 hours.
         */
        'license_cache_ttl_minutes' => (int) env('VOODFLOW_PORTAL_LICENSE_CACHE_MINUTES', 15),
        'http_timeout' => (int) env('VOODFLOW_PORTAL_HTTP_TIMEOUT', 10),
        /*
         * Optional override: explicit Composer http-basic hostnames (otherwise any *.composer.sh).
         */
        'repository_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('VOODFLOW_COMPOSER_REPOSITORY_HOSTS', ''))))),
        'composer_auth_extra_paths' => [],
        /*
         * Local dev: license key when Composer auth is unavailable.
         */
        'license_key_env_fallback' => env('VOODFLOW_LICENSE_KEY'),
        /*
         * Local dev: fingerprint when APP_URL host is wrong (e.g. localhost vs real domain).
         */
        'fingerprint_env_fallback' => env('VOODFLOW_LICENSE_FINGERPRINT'),
    ],

    /*
     * Multi-tenancy support.
     *
     * When disabled (default), no tenant scoping or tenant_id writes will be
     * performed, so the plugin behaves exactly like the current base version.
     */
    'tenancy' => [
        'enabled' => env('VOODFLOW_TENANCY_ENABLED', false),
        'tenant_id_column' => 'tenant_id',
    ],

    /*
     * Enable whitelabel mode.
     *
     * When enabled, certain admin-only safety features (like node blocking for
     * end-users) become available and enforced.
     */
    'whitelabel' => [
        'enabled' => env('VOODFLOW_WHITELABEL_ENABLED', false),
    ],

    /*
     * Enable the Nodes management UI (Installed Packages).
     *
     * When disabled, the "Nodes" page is hidden even for authorized users.
     */
    'nodes' => [
        'enabled' => env('VOODFLOW_NODES_ENABLED', false),
    ],

    'queue_connection' => env('VOODFLOW_QUEUE', null),

    /*
     * When true, run workflow triggers synchronously (no queue).
     * Useful for local development when no queue worker is running.
     * When false, jobs are queued (requires a running worker).
     */
    'execute_sync' => env('VOODFLOW_EXECUTE_SYNC', false),

    /*
     * Maximum number of retry attempts for failed executions.
     * Used by WorkflowResume service to prevent infinite retry loops.
     */
    'max_retry_attempts' => env('VOODFLOW_MAX_RETRY_ATTEMPTS', 3),

    /*
     * Use Redis for caching active execution contexts.
     * Improves performance for large workflows. Requires Redis.
     */
    'use_redis_cache' => env('VOODFLOW_USE_REDIS_CACHE', false),

    /*
     * Warn when context size exceeds this threshold (in KB).
     * Large contexts may impact performance.
     */
    'context_size_warning_threshold' => env('VOODFLOW_CONTEXT_SIZE_WARNING_KB', 500),

    /*
     * Date format used for tag replacement in nodes (e.g., SendMail, SendWebhook).
     * Uses PHP date() format: https://www.php.net/manual/en/datetime.format.php
     * Examples: 'd/m/Y H:i', 'Y-m-d', 'd-m-Y H:i:s'
     */
    'date_format' => env('VOODFLOW_DATE_FORMAT', 'd/m/Y H:i'),

    'models' => [
        'workflow' => Workflow::class,
        'node' => Node::class,
        'edge' => Edge::class,
        'execution' => Execution::class,
        'execution_node' => ExecutionNode::class,
    ],

    'table_names' => [
        'workflows' => 'voodflow_workflows',
        'nodes' => 'voodflow_nodes',
        'edges' => 'voodflow_edges',
        'executions' => 'voodflow_executions',
        'execution_nodes' => 'voodflow_execution_nodes',
        'model_integrations' => 'voodflow_model_integrations',
        'triggers' => 'voodflow_triggers',
        'actions' => 'voodflow_actions',
        'action_logs' => 'voodflow_action_logs',
    ],

    'editor' => [
        'tiptap' => [
            'toolbar_buttons' => [
                'bold',
                'italic',
                'underline',
                'strike',
                'bulletList',
                'orderedList',
                'h2',
                'h3',
                'blockquote',
                'link',
                'codeBlock',
            ],
        ],
    ],

    /*
     * DEPRECATED: Action handlers are now built into self-contained nodes
     * This config section is no longer used and will be removed in future versions.
     */
    'action_handlers' => [
        // Left empty - handlers moved into node classes
    ],

    'webhook' => [
        'secret' => env('VOODFLOW_WEBHOOK_SECRET'),
        'queue' => env('VOODFLOW_WEBHOOK_QUEUE'),
        'connection' => env('VOODFLOW_WEBHOOK_CONNECTION'),
        'timeout' => env('VOODFLOW_WEBHOOK_TIMEOUT'),
        'tries' => env('VOODFLOW_WEBHOOK_TRIES'),
        'backoff_strategy' => env('VOODFLOW_WEBHOOK_BACKOFF', 'Spatie\\WebhookServer\\BackoffStrategy\\ExponentialBackoffStrategy'),
        'verify_ssl' => env('VOODFLOW_WEBHOOK_VERIFY_SSL', true),
        'throw_exception_on_failure' => env('VOODFLOW_WEBHOOK_THROW_ON_FAILURE', false),
    ],

    /*
     * Model classes excluded from the Model Integration picker.
     * Use for internal models that should not be tracked.
     */
    'excluded_models' => [
        // \App\Models\InternalModel::class,
    ],
];
