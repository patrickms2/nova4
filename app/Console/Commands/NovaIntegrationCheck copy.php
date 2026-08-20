<?php

namespace App\Console\Commands;

use App\Models\NovaBusiness;
use App\Models\NovaMcpServer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class NovaIntegrationCheck extends Command
{
    protected $signature = 'nova:integration-check
        {--sirvo-url=https://sirvo.test : Base URL for Sirvo local server}
        {--lageria-url=https://lageriawp.test : Base URL for La Geria local WordPress}
        {--timeout=8 : HTTP timeout in seconds}';

    protected $description = 'Register and verify local Nova integrations for Sirvo and La Geria';

    public function handle(): int
    {
        $sirvoUrl = rtrim((string) $this->option('sirvo-url'), '/');
        $lageriaUrl = rtrim((string) $this->option('lageria-url'), '/');
        $timeout = (int) $this->option('timeout');

        $this->info('Registering local Nova integration records...');

        $sirvo = $this->registerBusiness(
            name: 'Sirvo',
            slug: 'sirvo',
            type: 'restaurant',
        );

        $lageria = $this->registerBusiness(
            name: 'La Geria',
            slug: 'la-geria',
            type: 'winery',
        );

        $sirvoServer = $this->registerMcpServer(
            business: $sirvo,
            name: 'Sirvo Local API',
            type: 'sirvo',
            endpointUrl: $sirvoUrl,
            capabilities: [
                'branches' => '/api/branches',
                'reservations' => '/api/reservations',
                'chat' => '/api/chat',
                'dashboard_reservations' => '/api/dashboard/reservations',
            ],
        );

        $lageriaServer = $this->registerMcpServer(
            business: $lageria,
            name: 'La Geria WordPress MCP',
            type: 'la_geria',
            endpointUrl: $lageriaUrl,
            capabilities: [
                'wordpress_rest' => '/wp-json/',
                'abilities' => '/wp-json/abilities/v1',
                'mcp' => '/wp-json/mcp/v1',
                'latepoint' => '/wp-json/',
            ],
        );

        $this->newLine();
        $this->info('Running local health checks...');

        $results = [
            $this->checkEndpoint($sirvoServer, '/api/config', $timeout, ['restaurantId es requerido']),
            $this->checkEndpoint($sirvoServer, '/api/branches', $timeout, ['Missing or invalid Authorization header']),
            $this->checkEndpoint($lageriaServer, '/wp-json/', $timeout),
            $this->checkEndpoint($lageriaServer, '/wp-json/mcp/v1', $timeout),
        ];

        $this->table(
            ['Service', 'URL', 'Status', 'OK', 'Message'],
            array_map(fn (array $result): array => [
                $result['service'],
                $result['url'],
                $result['status'],
                $result['ok'] ? 'yes' : 'no',
                $result['message'],
            ], $results),
        );

        $failed = collect($results)->where('ok', false)->count();

        if ($failed > 0) {
            $this->warn("Integration check completed with {$failed} failing endpoint(s).");
            $this->line('If Herd says "Site not found", link the local projects to the expected .test domains or pass --sirvo-url / --lageria-url.');

            return self::FAILURE;
        }

        $this->info('Integration check passed.');

        return self::SUCCESS;
    }

    private function registerBusiness(string $name, string $slug, string $type): NovaBusiness
    {
        return NovaBusiness::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'business_type' => $type,
                'status' => 'active',
                'subscription_amount' => 200,
                'commission_rate' => 10,
            ],
        );
    }

    private function registerMcpServer(NovaBusiness $business, string $name, string $type, string $endpointUrl, array $capabilities): NovaMcpServer
    {
        return NovaMcpServer::updateOrCreate(
            [
                'nova_business_id' => $business->id,
                'type' => $type,
            ],
            [
                'name' => $name,
                'endpoint_url' => $endpointUrl,
                'auth_type' => 'none',
                'status' => 'active',
                'capabilities' => $capabilities,
            ],
        );
    }

    private function checkEndpoint(NovaMcpServer $server, string $path, int $timeout, array $expectedErrorFragments = []): array
    {
        $url = rtrim($server->endpoint_url, '/').$path;

        try {
            $response = Http::withoutVerifying()
                ->timeout($timeout)
                ->acceptJson()
                ->get($url);

            $body = (string) $response->body();
            $isHerdMissingSite = str_contains($body, 'Herd - Site not found');
            $hasExpectedError = collect($expectedErrorFragments)
                ->contains(fn (string $fragment): bool => str_contains($body, $fragment));
            $ok = ($response->successful() || $hasExpectedError) && ! $isHerdMissingSite;

            $server->forceFill([
                'last_checked_at' => now(),
                'last_error' => $ok ? null : ($isHerdMissingSite ? 'Herd site not found' : $body),
                'status' => $ok ? 'active' : 'error',
            ])->save();

            return [
                'service' => $server->name,
                'url' => $url,
                'status' => $response->status(),
                'ok' => $ok,
                'message' => $ok ? ($hasExpectedError ? 'reachable, requires parameters/auth' : 'reachable') : ($isHerdMissingSite ? 'Herd site not found' : substr($body, 0, 120)),
            ];
        } catch (\Throwable $exception) {
            $server->forceFill([
                'last_checked_at' => now(),
                'last_error' => $exception->getMessage(),
                'status' => 'error',
            ])->save();

            return [
                'service' => $server->name,
                'url' => $url,
                'status' => 'ERR',
                'ok' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }
}
