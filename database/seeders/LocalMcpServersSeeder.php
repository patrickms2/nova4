<?php

namespace Database\Seeders;

use App\Models\Server;
use Illuminate\Database\Seeder;

class LocalMcpServersSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->servers() as $definition) {
            $server = Server::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'version' => '1.0.0',
                    'instructions' => $definition['instructions'],
                    'transport' => 'web',
                    'endpoint' => '/mcp/'.$definition['slug'],
                    'middleware' => [],
                    'metadata' => [
                        'remote_endpoint' => $definition['remote_endpoint'],
                        'source_project' => $definition['source_project'] ?? null,
                        'source_stack' => $definition['source_stack'],
                        'business' => $definition['business'],
                        'capabilities' => $definition['capabilities'],
                        'availability' => $definition['availability'] ?? null,
                        'auth_token_env' => $definition['auth_token_env'] ?? null,
                        'local_header' => $definition['local_header'] ?? null,
                        'login' => $definition['login'] ?? null,
                        'sync_targets' => $definition['sync_targets'] ?? [],
                        'imported_from' => 'local-mcp-hub',
                    ],
                    'is_active' => true,
                ],
            );

            $server->tools()->updateOrCreate(
                ['name' => 'remote-endpoint-info'],
                [
                    'title' => 'Remote Endpoint Info',
                    'description' => 'Returns the configured remote endpoint and local metadata for this MCP server.',
                    'input_schema' => [],
                    'output_schema' => [
                        'server' => ['type' => 'string', 'required' => true],
                        'remote_endpoint' => ['type' => 'string', 'required' => true],
                        'source_stack' => ['type' => 'array', 'required' => true],
                        'capabilities' => ['type' => 'object', 'required' => true],
                    ],
                    'handler_code' => $this->endpointInfoHandler($definition),
                    'annotations' => ['isReadOnly' => true, 'isIdempotent' => true, 'isOpenWorld' => false],
                    'metadata' => ['source' => 'local-mcp-hub'],
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            );

            $server->tools()->updateOrCreate(
                ['name' => 'remote-http-check'],
                [
                    'title' => 'Remote HTTP Check',
                    'description' => 'Performs a GET request against the configured remote endpoint plus an optional path.',
                    'input_schema' => [
                        'path' => [
                            'type' => 'string',
                            'description' => 'Path to request, for example /info, /tools, /wp-json/ or /api/config.',
                            'required' => false,
                            'default' => $definition['default_check_path'],
                        ],
                    ],
                    'output_schema' => [],
                    'handler_code' => $this->httpCheckHandler(
                        $definition['remote_endpoint'],
                        $definition['default_check_path'],
                        $definition['auth_token_env'] ?? null,
                        $definition['local_header'] ?? null,
                    ),
                    'annotations' => ['isReadOnly' => true, 'isIdempotent' => true, 'isOpenWorld' => true],
                    'metadata' => ['source' => 'local-mcp-hub'],
                    'is_active' => true,
                    'sort_order' => 2,
                ],
            );

            foreach ($definition['action_tools'] as $index => $toolDefinition) {
                $handlerCode = ($toolDefinition['method'] ?? 'get') === 'wp_ability_run'
                    ? $this->wpAbilityRunHandler(
                        $definition['remote_endpoint'],
                        $toolDefinition['ability'] ?? null,
                        $toolDefinition['auth_token_env'] ?? $definition['auth_token_env'] ?? null,
                        $toolDefinition['local_header'] ?? $definition['local_header'] ?? null,
                    )
                    : $this->remoteGetHandler(
                        $definition['remote_endpoint'],
                        $toolDefinition['path'],
                        $toolDefinition['auth_token_env'] ?? $definition['auth_token_env'] ?? null,
                        $definition['login'] ?? null,
                        $toolDefinition['local_header'] ?? $definition['local_header'] ?? null,
                    );

                $server->tools()->updateOrCreate(
                    ['name' => $toolDefinition['name']],
                    [
                        'title' => $toolDefinition['title'],
                        'description' => $toolDefinition['description'],
                        'input_schema' => $toolDefinition['input_schema'] ?? [
                            'query' => [
                                'type' => 'object',
                                'description' => 'Optional query parameters sent with the GET request.',
                                'required' => false,
                            ],
                        ],
                        'output_schema' => [],
                        'handler_code' => $handlerCode,
                        'annotations' => ['isReadOnly' => true, 'isIdempotent' => true, 'isOpenWorld' => true],
                        'metadata' => [
                            'source' => 'local-mcp-hub',
                            'remote_path' => $toolDefinition['path'],
                            'capability' => $toolDefinition['capability'],
                        ],
                        'is_active' => true,
                        'sort_order' => 10 + $index,
                    ],
                );
            }

            $server->resources()->updateOrCreate(
                ['name' => 'server-profile'],
                [
                    'title' => $definition['name'].' Profile',
                    'description' => 'Local profile, endpoint and capabilities for this imported MCP server.',
                    'uri' => $definition['slug'].'://profile',
                    'mime_type' => 'text/markdown',
                    'content' => $this->profileMarkdown($definition),
                    'handler_code' => null,
                    'annotations' => ['audience' => 'assistant', 'priority' => 0.8],
                    'metadata' => ['source' => 'local-mcp-hub'],
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            );

            $server->prompts()->updateOrCreate(
                ['name' => 'use-server'],
                [
                    'title' => 'Use '.$definition['name'],
                    'description' => 'Prompt template for planning a request against this imported MCP server.',
                    'arguments' => [
                        ['name' => 'task', 'description' => 'What the user wants to do with this server', 'required' => true],
                    ],
                    'messages' => [
                        [
                            'role' => 'assistant',
                            'content' => 'You are using '.$definition['name'].' through MCP Studio. Remote endpoint: '.$definition['remote_endpoint'],
                        ],
                        [
                            'role' => 'user',
                            'content' => 'Task: {task}',
                        ],
                    ],
                    'metadata' => ['source' => 'local-mcp-hub'],
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            );
        }
    }

    private function endpointInfoHandler(array $definition): string
    {
        return 'return json_encode([
    "server" => '.var_export($definition['name'], true).',
    "business" => '.var_export($definition['business'], true).',
    "remote_endpoint" => '.var_export($definition['remote_endpoint'], true).',
    "source_project" => '.var_export($definition['source_project'] ?? null, true).',
    "source_stack" => '.var_export($definition['source_stack'], true).',
    "capabilities" => '.var_export($definition['capabilities'], true).',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);';
    }

    private function httpCheckHandler(string $endpoint, string $defaultPath, ?string $authTokenEnv = null, ?array $localHeader = null): string
    {
        return '$baseUrl = '.var_export(rtrim($endpoint, '/'), true).';
$path = $input["path"] ?? '.var_export($defaultPath, true).';
$path = "/".ltrim((string) $path, "/");
$url = rtrim($baseUrl, "/").$path;
$authToken = '.($authTokenEnv ? 'env('.var_export($authTokenEnv, true).')' : 'null').';
$localHeaderName = '.var_export($localHeader['name'] ?? null, true).';
$localHeaderValue = '.(! empty($localHeader['env']) ? 'env('.var_export($localHeader['env'], true).')' : 'null').';

try {
    $client = \Illuminate\Support\Facades\Http::timeout(10);

    if (! empty($authToken)) {
        $client = $client->withToken((string) $authToken);
    }

    if (! empty($localHeaderName) && ! empty($localHeaderValue)) {
        $client = $client->withHeaders([(string) $localHeaderName => (string) $localHeaderValue]);
    }

    $response = $client->get($url);

    return json_encode([
        "url" => $url,
        "status" => $response->status(),
        "ok" => $response->successful(),
        "body_preview" => mb_substr((string) $response->body(), 0, 2000),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (\Throwable $exception) {
    return json_encode([
        "url" => $url,
        "status" => null,
        "ok" => false,
        "error" => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}';
    }

    private function remoteGetHandler(string $endpoint, string $path, ?string $authTokenEnv = null, ?array $login = null, ?array $localHeader = null): string
    {
        return '$baseUrl = '.var_export(rtrim($endpoint, '/'), true).';
$path = '.var_export('/'.ltrim($path, '/'), true).';
$query = $input["query"] ?? [];
$authToken = '.($authTokenEnv ? 'env('.var_export($authTokenEnv, true).')' : 'null').';
$loginPath = '.var_export($login['path'] ?? null, true).';
$loginEmail = '.(! empty($login['user_env']) ? 'env('.var_export($login['user_env'], true).')' : 'null').';
$loginPassword = '.(! empty($login['password_env']) ? 'env('.var_export($login['password_env'], true).')' : 'null').';
$localHeaderName = '.var_export($localHeader['name'] ?? null, true).';
$localHeaderValue = '.(! empty($localHeader['env']) ? 'env('.var_export($localHeader['env'], true).')' : 'null').';

if (! is_array($query)) {
    $decodedQuery = json_decode((string) $query, true);
    $query = is_array($decodedQuery) ? $decodedQuery : [];
}

if (str_contains($path, "{id}") && isset($input["id"])) {
    $path = str_replace("{id}", rawurlencode((string) $input["id"]), $path);
}

$url = rtrim($baseUrl, "/").$path;

try {
    if (empty($authToken) && ! empty($loginPath) && ! empty($loginEmail) && ! empty($loginPassword)) {
        $loginResponse = \Illuminate\Support\Facades\Http::timeout(15)->post(
            rtrim($baseUrl, "/")."/".ltrim((string) $loginPath, "/"),
            [
                "email" => (string) $loginEmail,
                "password" => (string) $loginPassword,
            ],
        );

        if ($loginResponse->successful()) {
            $loginData = $loginResponse->json();
            $authToken = $loginData["session"]["access_token"] ?? null;
        }
    }

    $client = \Illuminate\Support\Facades\Http::timeout(15);

    if (! empty($authToken)) {
        $client = $client->withToken((string) $authToken);
    }

    if (! empty($localHeaderName) && ! empty($localHeaderValue)) {
        $client = $client->withHeaders([(string) $localHeaderName => (string) $localHeaderValue]);
    }

    $response = $client->get($url, $query);
    $contentType = $response->header("content-type");
    $body = (string) $response->body();
    $decoded = json_decode($body, true);

    return json_encode([
        "url" => $url,
        "query" => $query,
        "status" => $response->status(),
        "ok" => $response->successful(),
        "content_type" => $contentType,
        "data" => json_last_error() === JSON_ERROR_NONE ? $decoded : null,
        "body_preview" => json_last_error() === JSON_ERROR_NONE ? null : mb_substr($body, 0, 4000),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (\Throwable $exception) {
    return json_encode([
        "url" => $url,
        "query" => $query,
        "status" => null,
        "ok" => false,
        "error" => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}';
    }

    private function wpAbilityRunHandler(string $endpoint, ?string $fixedAbility = null, ?string $authTokenEnv = null, ?array $localHeader = null): string
    {
        return '$baseUrl = '.var_export(rtrim($endpoint, '/'), true).';
$fixedAbility = '.var_export($fixedAbility, true).';
$ability = $fixedAbility ?: ($input["ability"] ?? "latepoint/list-bookings");
$payload = $input["input"] ?? $input;
$authToken = '.($authTokenEnv ? 'env('.var_export($authTokenEnv, true).')' : 'null').';
$localHeaderName = '.var_export($localHeader['name'] ?? null, true).';
$localHeaderValue = '.(! empty($localHeader['env']) ? 'env('.var_export($localHeader['env'], true).')' : 'null').';

if (! is_array($payload)) {
    $decodedPayload = json_decode((string) $payload, true);
    $payload = is_array($decodedPayload) ? $decodedPayload : [];
}

if (! empty($fixedAbility) && array_key_exists("input", $payload) && is_array($payload["input"])) {
    $payload = $payload["input"];
}

$ability = trim((string) $ability, "/");
$url = rtrim($baseUrl, "/")."/wp-json/wp-abilities/v1/abilities/".$ability."/run";

try {
    $client = \Illuminate\Support\Facades\Http::timeout(20);

    if (! empty($authToken)) {
        $client = $client->withToken((string) $authToken);
    }

    if (! empty($localHeaderName) && ! empty($localHeaderValue)) {
        $client = $client->withHeaders([(string) $localHeaderName => (string) $localHeaderValue]);
    }

    $response = $client->post($url, ["input" => $payload]);
    $contentType = $response->header("content-type");
    $body = (string) $response->body();
    $decoded = json_decode($body, true);

    return json_encode([
        "url" => $url,
        "ability" => $ability,
        "input" => $payload,
        "status" => $response->status(),
        "ok" => $response->successful(),
        "content_type" => $contentType,
        "data" => json_last_error() === JSON_ERROR_NONE ? $decoded : null,
        "body_preview" => json_last_error() === JSON_ERROR_NONE ? null : mb_substr($body, 0, 4000),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (\Throwable $exception) {
    return json_encode([
        "url" => $url,
        "ability" => $ability,
        "input" => $payload,
        "status" => null,
        "ok" => false,
        "error" => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}';
    }

    private function profileMarkdown(array $definition): string
    {
        $capabilities = collect($definition['capabilities'])
            ->map(fn (string $path, string $name): string => "- {$name}: `{$path}`")
            ->implode("\n");

        $stack = collect($definition['source_stack'])
            ->map(fn (string $item): string => "`{$item}`")
            ->implode(', ');

        return <<<MARKDOWN
# {$definition['name']}

Business: {$definition['business']}

Remote endpoint: `{$definition['remote_endpoint']}`

Source project: `{$definition['source_project']}`

Stack: {$stack}

## Capabilities

{$capabilities}
MARKDOWN;
    }

    private function servers(): array
    {
        return [
            [
                'name' => 'Sirvo Restaurantes MCP',
                'slug' => 'sirvo-restaurantes',
                'business' => 'Sirvo',
                'description' => 'Sirvo restaurants MCP server for branches, reservations and chat.',
                'instructions' => 'Use this server for restaurant availability, reservations, branch data and restaurant chat workflows.',
                'remote_endpoint' => rtrim((string) env('SIRVO_ENDPOINT_URL', 'http://192.168.1.42:3000'), '/'),
                'auth_token_env' => 'SIRVO_BEARER_TOKEN',
                'login' => [
                    'path' => '/api/auth/login',
                    'user_env' => 'SIRVO_USER',
                    'password_env' => 'SIRVO_PASSWORD',
                ],
                'source_project' => env('SIRVO_LOCAL_PATH', '/Users/patrickms/Sites/localhost/sirvo'),
                'source_stack' => ['sirvo', 'restaurants', 'mcp'],
                'default_check_path' => '/api/config',
                'capabilities' => [
                    'config' => '/api/config',
                    'branches' => '/api/branches',
                    'reservations' => '/api/reservations',
                    'dashboard_reservations' => '/api/dashboard/reservations',
                    'chat' => '/api/chat',
                ],
                'sync_targets' => [
                    [
                        'capability' => 'branches',
                        'source_platform' => 'sirvo',
                        'resource_type' => 'restaurant',
                        'target_model' => 'restaurant',
                        'source_label_suffix' => 'Restaurantes',
                    ],
                    [
                        'capability' => 'dashboard_reservations',
                        'source_platform' => 'sirvo',
                        'resource_type' => 'restaurant_booking',
                        'target_model' => 'restaurant_booking',
                        'source_label_suffix' => 'Reservas restaurante',
                    ],
                ],
                'action_tools' => [
                    [
                        'name' => 'sirvo-config',
                        'title' => 'Sirvo Config',
                        'description' => 'Reads the Sirvo local configuration endpoint.',
                        'path' => '/api/config',
                        'capability' => 'config',
                    ],
                    [
                        'name' => 'sirvo-branches',
                        'title' => 'Sirvo Branches',
                        'description' => 'Lists available Sirvo restaurant branches.',
                        'path' => '/api/branches',
                        'capability' => 'branches',
                    ],
                    [
                        'name' => 'sirvo-dashboard-reservations',
                        'title' => 'Sirvo Dashboard Reservations',
                        'description' => 'Reads Sirvo dashboard reservations with optional query filters.',
                        'path' => '/api/dashboard/reservations',
                        'capability' => 'dashboard_reservations',
                    ],
                ],
            ],
            [
                'name' => 'La Geria WordPress Woo LatePoint MCP',
                'slug' => 'la-geria-wordpress-woo-latepoint',
                'business' => 'La Geria',
                'description' => 'La Geria WordPress, WooCommerce and LatePoint MCP server.',
                'instructions' => 'Use this server for La Geria WordPress, WooCommerce orders, LatePoint bookings, customers and invoices.',
                'remote_endpoint' => rtrim((string) env('LAGERIA_ENDPOINT_URL', 'https://lageriawp.test'), '/'),
                'auth_token_env' => 'LAGERIA_BEARER_TOKEN',
                'local_header' => [
                    'name' => 'X-MCP-Studio-Token',
                    'env' => 'LAGERIA_MCP_LOCAL_TOKEN',
                ],
                'source_project' => env('LAGERIA_LOCAL_PATH', '/Users/patrickms/Downloads/lageria'),
                'source_stack' => ['wordpress', 'woocommerce', 'latepoint', 'mcp'],
                'default_check_path' => '/wp-json/',
                'capabilities' => [
                    'wordpress_rest' => '/wp-json/',
                    'woocommerce_orders' => '/wp-json/wc/v3/orders',
                    'latepoint_services' => '/wp-json/wp-abilities/v1/abilities/latepoint/get-services/run',
                    'latepoint_bookings' => '/wp-json/wp-abilities/v1/abilities/latepoint/list-bookings/run',
                    'latepoint_transactions' => '/wp-json/wp-abilities/v1/abilities/latepoint/list-transactions/run',
                    'latepoint_invoices' => '/wp-json/wp-abilities/v1/abilities',
                    'abilities' => '/wp-json/wp-abilities/v1',
                    'mcp' => '/wp-json/mcp/v1',
                ],
                'availability' => [
                    'times' => ['11:00', '11:30', '12:00', '12:45', '13:00', '15:00', '16:00'],
                ],
                'sync_targets' => [
                    [
                        'capability' => 'woocommerce_orders',
                        'source_platform' => 'woo',
                        'resource_type' => 'wine_product',
                        'target_model' => 'external_catalog_item',
                        'source_label_suffix' => 'Vinos',
                    ],
                    [
                        'capability' => 'latepoint_services',
                        'source_platform' => 'latepoint',
                        'resource_type' => 'tour_visit',
                        'target_model' => 'tour',
                        'source_label_suffix' => 'Visitas',
                        'default_location' => [
                            'address' => 'La Geria',
                            'city' => 'Yaiza',
                            'country' => 'Spain',
                            'latitude' => 28.972,
                            'longitude' => -13.706,
                        ],
                    ],
                    [
                        'capability' => 'latepoint_bookings',
                        'source_platform' => 'latepoint',
                        'resource_type' => 'tour_booking',
                        'target_model' => 'tour_booking',
                        'source_label_suffix' => 'Reservas visitas',
                    ],
                    [
                        'capability' => 'latepoint_transactions',
                        'source_platform' => 'latepoint',
                        'resource_type' => 'tour_booking',
                        'target_model' => 'external_payment',
                        'source_label_suffix' => 'Pagos visitas',
                    ],
                ],
                'action_tools' => [
                    [
                        'name' => 'lageria-wordpress-index',
                        'title' => 'La Geria WordPress REST Index',
                        'description' => 'Reads the La Geria WordPress REST index.',
                        'path' => '/wp-json/',
                        'capability' => 'wordpress_rest',
                    ],
                    [
                        'name' => 'lageria-abilities',
                        'title' => 'La Geria Abilities',
                        'description' => 'Reads the WordPress Abilities endpoint if enabled.',
                        'path' => '/wp-json/wp-abilities/v1/abilities',
                        'capability' => 'abilities',
                        'input_schema' => [
                            'query' => [
                                'type' => 'object',
                                'description' => 'Optional query parameters, for example {"per_page": 100, "category": "latepoint"}.',
                                'required' => false,
                                'default' => ['per_page' => 100],
                            ],
                        ],
                    ],
                    [
                        'name' => 'lageria-mcp-index',
                        'title' => 'La Geria MCP Index',
                        'description' => 'Reads the La Geria WordPress MCP endpoint if enabled.',
                        'path' => '/wp-json/mcp/v1',
                        'capability' => 'mcp',
                    ],
                    [
                        'name' => 'lageria-wp-abilities-categories',
                        'title' => 'La Geria Abilities Categories',
                        'description' => 'Lists WordPress Abilities categories available in La Geria.',
                        'path' => '/wp-json/wp-abilities/v1/categories',
                        'capability' => 'abilities',
                    ],
                    [
                        'name' => 'lageria-latepoint-abilities',
                        'title' => 'La Geria LatePoint Abilities',
                        'description' => 'Lists LatePoint abilities exposed by WordPress Abilities.',
                        'path' => '/wp-json/wp-abilities/v1/abilities',
                        'capability' => 'latepoint_bookings',
                        'input_schema' => [
                            'query' => [
                                'type' => 'object',
                                'description' => 'Optional query parameters for the abilities list.',
                                'required' => false,
                                'default' => ['category' => 'latepoint', 'per_page' => 100],
                            ],
                        ],
                    ],
                    [
                        'name' => 'lageria-latepoint-run-ability',
                        'title' => 'La Geria LatePoint Run Ability',
                        'description' => 'Runs any La Geria LatePoint ability by name through WordPress Abilities.',
                        'path' => '/wp-json/wp-abilities/v1/abilities/{ability}/run',
                        'capability' => 'latepoint_bookings',
                        'method' => 'wp_ability_run',
                        'input_schema' => [
                            'ability' => [
                                'type' => 'string',
                                'description' => 'Ability name, for example latepoint/list-bookings.',
                                'required' => true,
                                'default' => 'latepoint/list-bookings',
                            ],
                            'input' => [
                                'type' => 'object',
                                'description' => 'Payload sent as the ability input.',
                                'required' => false,
                                'default' => ['per_page' => 5],
                            ],
                        ],
                    ],
                    [
                        'name' => 'lageria-latepoint-list-bookings',
                        'title' => 'La Geria LatePoint List Bookings',
                        'description' => 'Lists La Geria LatePoint bookings through the LatePoint MCP ability.',
                        'path' => '/wp-json/wp-abilities/v1/abilities/latepoint/list-bookings/run',
                        'capability' => 'latepoint_bookings',
                        'method' => 'wp_ability_run',
                        'ability' => 'latepoint/list-bookings',
                        'input_schema' => [
                            'input' => [
                                'type' => 'object',
                                'description' => 'LatePoint list filters, for example {"per_page": 5}.',
                                'required' => false,
                                'default' => ['per_page' => 5],
                            ],
                        ],
                    ],
                    [
                        'name' => 'lageria-latepoint-list-services',
                        'title' => 'La Geria LatePoint List Services',
                        'description' => 'Lists La Geria LatePoint services so they can be synced as tour visits.',
                        'path' => '/wp-json/wp-abilities/v1/abilities/latepoint/get-services/run',
                        'capability' => 'latepoint_services',
                        'method' => 'wp_ability_run',
                        'ability' => 'latepoint/get-services',
                        'input_schema' => [
                            'input' => [
                                'type' => 'object',
                                'description' => 'LatePoint service list filters, for example {"per_page": 50}.',
                                'required' => false,
                                'default' => ['per_page' => 50],
                            ],
                        ],
                    ],
                    [
                        'name' => 'lageria-latepoint-get-booking',
                        'title' => 'La Geria LatePoint Get Booking',
                        'description' => 'Gets one La Geria LatePoint booking by ID through the LatePoint MCP ability.',
                        'path' => '/wp-json/wp-abilities/v1/abilities/latepoint/get-booking/run',
                        'capability' => 'latepoint_bookings',
                        'method' => 'wp_ability_run',
                        'ability' => 'latepoint/get-booking',
                        'input_schema' => [
                            'input' => [
                                'type' => 'object',
                                'description' => 'Payload with booking ID, for example {"id": 188}.',
                                'required' => true,
                                'default' => ['id' => 188],
                            ],
                        ],
                    ],
                    [
                        'name' => 'lageria-latepoint-upcoming-bookings',
                        'title' => 'La Geria LatePoint Upcoming Bookings',
                        'description' => 'Lists upcoming La Geria LatePoint bookings through the LatePoint MCP ability.',
                        'path' => '/wp-json/wp-abilities/v1/abilities/latepoint/get-upcoming-bookings/run',
                        'capability' => 'latepoint_bookings',
                        'method' => 'wp_ability_run',
                        'ability' => 'latepoint/get-upcoming-bookings',
                        'input_schema' => [
                            'input' => [
                                'type' => 'object',
                                'description' => 'LatePoint upcoming booking filters.',
                                'required' => false,
                                'default' => ['per_page' => 5],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Taxilanz Rutas Woo MCP',
                'slug' => 'taxilanz-rutas-woo',
                'business' => 'Taxilanz Rutas',
                'description' => 'Taxilanz routes WordPress and WooCommerce MCP server.',
                'instructions' => 'Use this server for Taxilanz route catalog, WooCommerce orders, customers and payments.',
                'remote_endpoint' => rtrim((string) env('TAXILANZ_ENDPOINT_URL', 'https://taxilanzwp.test'), '/'),
                'auth_token_env' => 'TAXILANZ_BEARER_TOKEN',
                'source_project' => env('TAXILANZ_RUTAS_LOCAL_PATH', '/Users/patrickms/Downloads/tourist'),
                'source_stack' => ['wordpress', 'woocommerce', 'routes', 'mcp'],
                'default_check_path' => '/wp-json/',
                'capabilities' => [
                    'wordpress_rest' => '/wp-json/',
                    'woocommerce_orders' => '/wp-json/wc/v3/orders',
                    'routes' => '/wp-json/',
                    'customers' => '/wp-json/wc/v3/customers',
                    'payments' => '/wp-json/wc/v3/orders',
                ],
                'sync_targets' => [
                    [
                        'capability' => 'routes',
                        'source_platform' => 'woo',
                        'resource_type' => 'tour_route',
                        'target_model' => 'tour',
                        'source_label_suffix' => 'Rutas',
                        'status' => 'paused',
                    ],
                    [
                        'capability' => 'woocommerce_orders',
                        'source_platform' => 'woo',
                        'resource_type' => 'tour_booking',
                        'target_model' => 'tour_booking',
                        'source_label_suffix' => 'Reservas rutas',
                        'status' => 'paused',
                    ],
                ],
                'action_tools' => [
                    [
                        'name' => 'taxilanz-wordpress-index',
                        'title' => 'Taxilanz WordPress REST Index',
                        'description' => 'Reads the Taxilanz WordPress REST index.',
                        'path' => '/wp-json/',
                        'capability' => 'wordpress_rest',
                    ],
                    [
                        'name' => 'taxilanz-woo-orders',
                        'title' => 'Taxilanz Woo Orders',
                        'description' => 'Reads WooCommerce orders. Requires the remote site to accept current auth/query parameters.',
                        'path' => '/wp-json/wc/v3/orders',
                        'capability' => 'woocommerce_orders',
                    ],
                ],
            ],
            [
                'name' => 'Taxilanz Chauffeur Booking MCP',
                'slug' => 'taxilanz-chauffeur-booking',
                'business' => 'Taxilanz Rutas',
                'description' => 'Taxilanz Chauffeur Taxi Booking System and WooCommerce local MCP server.',
                'instructions' => 'Use this server for Taxilanz Chauffeur taxi bookings, upcoming route reservations and related WooCommerce orders.',
                'remote_endpoint' => rtrim((string) env('TAXILANZ_ENDPOINT_URL', 'https://taxilanzwp.test'), '/'),
                'auth_token_env' => 'TAXILANZ_BEARER_TOKEN',
                'local_header' => [
                    'name' => 'X-MCP-Studio-Token',
                    'env' => 'TAXILANZ_MCP_LOCAL_TOKEN',
                ],
                'source_project' => env('TAXILANZ_WP_LOCAL_PATH', '/Users/patrickms/Downloads/wordpress7'),
                'source_stack' => ['wordpress', 'woocommerce', 'chauffeur-booking-system', 'mcp'],
                'default_check_path' => '/wp-json/taxilanz-mcp/v1/chauffeur/bookings',
                'capabilities' => [
                    'chauffeur_bookings' => '/wp-json/taxilanz-mcp/v1/chauffeur/bookings',
                    'chauffeur_booking' => '/wp-json/taxilanz-mcp/v1/chauffeur/bookings/{id}',
                    'chauffeur_upcoming_bookings' => '/wp-json/taxilanz-mcp/v1/chauffeur/upcoming-bookings',
                    'chauffeur_routes' => '/wp-json/taxilanz-mcp/v1/chauffeur/routes',
                    'woocommerce_orders' => '/wp-json/taxilanz-mcp/v1/woo/orders',
                    'woocommerce_order' => '/wp-json/taxilanz-mcp/v1/woo/orders/{id}',
                ],
                'sync_targets' => [
                    [
                        'capability' => 'chauffeur_bookings',
                        'source_platform' => 'woo',
                        'resource_type' => 'taxi',
                        'target_model' => 'taxi_service',
                        'source_label_suffix' => 'Taxis',
                    ],
                    [
                        'capability' => 'chauffeur_upcoming_bookings',
                        'source_platform' => 'woo',
                        'resource_type' => 'taxi_booking',
                        'target_model' => 'taxi_booking',
                        'source_label_suffix' => 'Reservas taxi',
                    ],
                    [
                        'capability' => 'chauffeur_routes',
                        'source_platform' => 'woo',
                        'resource_type' => 'tour_route',
                        'target_model' => 'tour',
                        'source_label_suffix' => 'Rutas Chauffeur',
                    ],
                ],
                'action_tools' => [
                    [
                        'name' => 'taxilanz-chauffeur-list-bookings',
                        'title' => 'Taxilanz Chauffeur List Bookings',
                        'description' => 'Lists Chauffeur Taxi Booking System bookings with optional filters.',
                        'path' => '/wp-json/taxilanz-mcp/v1/chauffeur/bookings',
                        'capability' => 'chauffeur_bookings',
                        'input_schema' => [
                            'query' => [
                                'type' => 'object',
                                'description' => 'Optional filters: {"per_page": 10, "page": 1, "status": 1, "date_from": "2026-05-01", "date_to": "2026-05-31", "orderby": "pickup_datetime", "order": "ASC"}.',
                                'required' => false,
                                'default' => ['per_page' => 10],
                            ],
                        ],
                    ],
                    [
                        'name' => 'taxilanz-chauffeur-upcoming-bookings',
                        'title' => 'Taxilanz Chauffeur Upcoming Bookings',
                        'description' => 'Lists upcoming Chauffeur Taxi Booking System bookings ordered by pickup datetime.',
                        'path' => '/wp-json/taxilanz-mcp/v1/chauffeur/upcoming-bookings',
                        'capability' => 'chauffeur_upcoming_bookings',
                        'input_schema' => [
                            'query' => [
                                'type' => 'object',
                                'description' => 'Optional filters: {"per_page": 10, "status": 1, "date_from": "2026-05-23 00:00:00"}.',
                                'required' => false,
                                'default' => ['per_page' => 10],
                            ],
                        ],
                    ],
                    [
                        'name' => 'taxilanz-chauffeur-list-routes',
                        'title' => 'Taxilanz Chauffeur List Routes',
                        'description' => 'Lists Chauffeur Taxi Booking System routes from chbs_route so they can be synced as tour routes.',
                        'path' => '/wp-json/taxilanz-mcp/v1/chauffeur/routes',
                        'capability' => 'chauffeur_routes',
                        'input_schema' => [
                            'query' => [
                                'type' => 'object',
                                'description' => 'Optional filters: {"per_page": 50, "page": 1, "search": "Timanfaya", "status": "publish"}.',
                                'required' => false,
                                'default' => ['per_page' => 50],
                            ],
                        ],
                    ],
                    [
                        'name' => 'taxilanz-chauffeur-get-booking',
                        'title' => 'Taxilanz Chauffeur Get Booking',
                        'description' => 'Gets a single Chauffeur Taxi Booking System booking by ID, including raw chbs_* meta.',
                        'path' => '/wp-json/taxilanz-mcp/v1/chauffeur/bookings/{id}',
                        'capability' => 'chauffeur_booking',
                        'input_schema' => [
                            'id' => [
                                'type' => 'integer',
                                'description' => 'Chauffeur booking post ID.',
                                'required' => true,
                                'default' => 226256,
                            ],
                            'query' => [
                                'type' => 'object',
                                'description' => 'Optional query parameters.',
                                'required' => false,
                                'default' => [],
                            ],
                        ],
                    ],
                    [
                        'name' => 'taxilanz-local-woo-orders',
                        'title' => 'Taxilanz Local Woo Orders',
                        'description' => 'Lists WooCommerce orders through the local Taxilanz MCP endpoint.',
                        'path' => '/wp-json/taxilanz-mcp/v1/woo/orders',
                        'capability' => 'woocommerce_orders',
                        'input_schema' => [
                            'query' => [
                                'type' => 'object',
                                'description' => 'Optional filters: {"per_page": 10, "page": 1, "status": "processing"}.',
                                'required' => false,
                                'default' => ['per_page' => 10],
                            ],
                        ],
                    ],
                    [
                        'name' => 'taxilanz-local-woo-order',
                        'title' => 'Taxilanz Local Woo Order',
                        'description' => 'Gets a single WooCommerce order by ID through the local Taxilanz MCP endpoint.',
                        'path' => '/wp-json/taxilanz-mcp/v1/woo/orders/{id}',
                        'capability' => 'woocommerce_order',
                        'input_schema' => [
                            'id' => [
                                'type' => 'integer',
                                'description' => 'WooCommerce order ID.',
                                'required' => true,
                                'default' => 226256,
                            ],
                            'query' => [
                                'type' => 'object',
                                'description' => 'Optional query parameters.',
                                'required' => false,
                                'default' => [],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Taxilanz Hoteles Laravel MCP',
                'slug' => 'taxilanz-hoteles-laravel',
                'business' => 'Taxilanz Hoteles',
                'description' => 'Taxilanz Hoteles Laravel and Filament MCP server.',
                'instructions' => 'Use this server for Taxilanz Hoteles Laravel, Filament and local hotel workflows.',
                'remote_endpoint' => rtrim((string) env('TAXILANZ_HOTELES_ENDPOINT_URL', 'https://taxilanzhrnew.test/api/mcp'), '/'),
                'auth_token_env' => 'TAXILANZ_HOTELES_BEARER_TOKEN',
                'source_project' => env('TAXILANZ_HOTELES_LOCAL_PATH', '/Users/patrickms/Downloads/taxilanzhrnew'),
                'source_stack' => ['laravel', 'filament', 'mcp'],
                'default_check_path' => '/info',
                'capabilities' => [
                    'info' => '/info',
                    'tools' => '/tools',
                    'execute' => '/execute',
                    'filament' => '/app',
                    'api' => '/api',
                ],
                'sync_targets' => [
                    [
                        'capability' => 'api',
                        'source_platform' => 'mcp',
                        'resource_type' => 'hotel',
                        'target_model' => 'hotel',
                        'source_label_suffix' => 'Hoteles',
                    ],
                ],
                'action_tools' => [
                    [
                        'name' => 'taxilanz-hoteles-mcp-info',
                        'title' => 'Taxilanz Hoteles MCP Info',
                        'description' => 'Reads the Taxilanz Hoteles MCP info endpoint.',
                        'path' => '/info',
                        'capability' => 'info',
                    ],
                    [
                        'name' => 'taxilanz-hoteles-mcp-tools',
                        'title' => 'Taxilanz Hoteles MCP Tools',
                        'description' => 'Reads the Taxilanz Hoteles MCP tools endpoint.',
                        'path' => '/tools',
                        'capability' => 'tools',
                    ],
                ],
            ],
            [
                'name' => 'Lanzaloe Magento MCP',
                'slug' => 'lanzaloe-magento',
                'business' => 'Lanzaloe',
                'description' => 'Lanzaloe Magento MCP server.',
                'instructions' => 'Use this server for Lanzaloe Magento catalog, stock, customers and order workflows.',
                'remote_endpoint' => rtrim((string) env('LANZALOE_ENDPOINT_URL', 'https://lanzaloe.novagestion.eu'), '/'),
                'auth_token_env' => 'LANZALOE_BEARER_TOKEN',
                'source_project' => null,
                'source_stack' => ['magento', 'mcp'],
                'default_check_path' => '/rest/all/V1',
                'capabilities' => [
                    'orders' => '/rest/all/V1/orders',
                    'products' => '/rest/all/V1/products',
                    'customers' => '/rest/all/V1/customers',
                    'stock' => '/rest/all/V1/stockItems',
                    'sync' => '/rest/all/V1',
                ],
                'sync_targets' => [
                    [
                        'capability' => 'products',
                        'source_platform' => 'magento',
                        'resource_type' => 'aloe_product',
                        'target_model' => 'external_catalog_item',
                        'source_label_suffix' => 'Aloe',
                    ],
                ],
                'action_tools' => [
                    [
                        'name' => 'lanzaloe-magento-products',
                        'title' => 'Lanzaloe Magento Products',
                        'description' => 'Reads Magento products. Requires Magento auth/query parameters if the endpoint is protected.',
                        'path' => '/rest/all/V1/products',
                        'capability' => 'products',
                    ],
                    [
                        'name' => 'lanzaloe-magento-orders',
                        'title' => 'Lanzaloe Magento Orders',
                        'description' => 'Reads Magento orders. Requires Magento auth/query parameters if the endpoint is protected.',
                        'path' => '/rest/all/V1/orders',
                        'capability' => 'orders',
                    ],
                ],
            ],
        ];
    }
}
