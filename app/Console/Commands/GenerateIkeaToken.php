<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GenerateIkeaToken extends Command
{
    protected $signature = 'domotics:ikea:token {hub} {--name=nova-domotics}';

    protected $description = 'Genera un token para el hub IKEA Dirigera (pulsa el botón del hub cuando se indique).';

    public function handle(): int
    {
        $hub = $this->argument('hub');
        $name = $this->option('name');

        $verifier = $this->generateCodeVerifier();
        $challenge = $this->generateCodeChallenge($verifier);

        $this->info('Solicitando código de autorización...');

        $authorize = Http::withOptions(['verify' => false])
            ->get("https://{$hub}:8443/v1/oauth/authorize", [
                'audience' => 'homesmart.local',
                'response_type' => 'code',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ]);

        if (! $authorize->successful() || ! $authorize->json('code')) {
            $this->error('No se pudo obtener el código de autorización.');
            $this->error($authorize->body());

            return self::FAILURE;
        }

        $code = $authorize->json('code');

        $this->warn('Pulsa el botón de acción del hub Dirigera y luego presiona ENTER...');
        $this->ask('Presiona ENTER cuando hayas pulsado el botón');

        $token = Http::withOptions(['verify' => false])
            ->asForm()
            ->post("https://{$hub}:8443/v1/oauth/token", [
                'code' => $code,
                'name' => $name,
                'grant_type' => 'authorization_code',
                'code_verifier' => $verifier,
            ]);

        if (! $token->successful() || ! $token->json('access_token')) {
            $this->error('No se pudo obtener el token.');
            $this->error($token->body());

            return self::FAILURE;
        }

        $this->info('Token generado:');
        $this->line($token->json('access_token'));
        $this->info("Añádelo al .env: DOMOTICS_IKEA_TOKEN={$token->json('access_token')}");

        return self::SUCCESS;
    }

    protected function generateCodeVerifier(): string
    {
        return $this->base64UrlEncode(random_bytes(32));
    }

    protected function generateCodeChallenge(string $verifier): string
    {
        return $this->base64UrlEncode(hash('sha256', $verifier, true));
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), ['+' => '-', '/' => '_']), '=');
    }
}
