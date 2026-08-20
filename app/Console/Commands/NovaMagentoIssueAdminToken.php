<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class NovaMagentoIssueAdminToken extends Command
{
    protected $signature = 'nova:magento-issue-admin-token {--store=lanzaloe : Environment store prefix, currently lanzaloe}';

    protected $description = 'Request a Magento admin token from credentials stored in environment variables without printing the token';

    public function handle(): int
    {
        $store = Str::upper((string) $this->option('store'));
        $baseUrl = (string) env("NOVA_{$store}_MAGENTO_URL");
        $username = (string) env("NOVA_{$store}_MAGENTO_USERNAME");
        $password = (string) env("NOVA_{$store}_MAGENTO_PASSWORD");

        if ($baseUrl === '' || $username === '' || $password === '') {
            $this->warn("Missing NOVA_{$store}_MAGENTO_URL, NOVA_{$store}_MAGENTO_USERNAME or NOVA_{$store}_MAGENTO_PASSWORD.");

            return self::FAILURE;
        }

        $endpoint = $this->tokenEndpoint($baseUrl);
        $response = Http::acceptJson()
            ->asJson()
            ->timeout(30)
            ->post($endpoint, [
                'username' => $username,
                'password' => $password,
            ]);

        if (! $response->successful()) {
            $this->error('Magento token request failed: HTTP '.$response->status());
            $this->line(Str::limit($response->body(), 500));

            return self::FAILURE;
        }

        $token = trim((string) $response->json());

        if ($token === '') {
            $this->error('Magento token response was empty.');

            return self::FAILURE;
        }

        $this->info("Token received. Add it to NOVA_{$store}_MAGENTO_TOKEN in .env.");
        $this->line('Token preview: '.$token);

        $this->line('Token preview: '.Str::mask($token, '*', 6, max(strlen($token) - 12, 0)));

        return self::SUCCESS;
    }

    private function tokenEndpoint(string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        if (str_contains($baseUrl, '/rest/')) {
            return preg_replace('#/V1.*$#', '/V1/integration/admin/token', $baseUrl) ?: $baseUrl.'/integration/admin/token';
        }

        return $baseUrl.'/rest/all/V1/integration/admin/token';
    }
}
