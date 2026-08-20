<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Spatie\Browsershot\Browsershot;

class CaptureScreenshots extends Command
{
    protected $signature = 'app:capture-screenshots
        {--guard=web : Auth guard (web or taxista)}
        {--user= : User ID to authenticate as}
        {--only= : Comma-separated list of screenshot keys to capture}
        {--width=1280 : Viewport width}
        {--height=800 : Viewport height}';

    protected $description = 'Capture screenshots of the app panels for documentation manuals';

    public function handle(): int
    {
        $outputDir = public_path('docs/img');
        File::ensureDirectoryExists($outputDir);

        $guard = (string) $this->option('guard');
        $width = (int) $this->option('width');
        $height = (int) $this->option('height');

        $userId = $this->option('user');
        if (! $userId) {
            $userId = $guard === 'taxista'
                ? User::where('role', 'taxista')->whereNotNull('email')->value('id')
                : User::where('role', 'admin')->orWhere('role', 'super_admin')->value('id');
        }

        $user = User::findOrFail((int) $userId);
        $this->info("Authenticating as: {$user->name} (ID: {$user->id}) via guard [{$guard}]");

        $sessionCookie = $this->getSessionCookie($user, $guard);
        if (! $sessionCookie) {
            $this->error('Could not generate session cookie.');
            return self::FAILURE;
        }

        $baseUrl = rtrim(config('app.url'), '/');
        $screenshots = $this->getScreenshots($guard, $baseUrl);

        $only = $this->option('only');
        if ($only) {
            $keys = array_map('trim', explode(',', (string) $only));
            $screenshots = array_filter($screenshots, fn (array $s): bool => in_array($s['key'], $keys, true));
        }

        $this->info(count($screenshots) . ' screenshots to capture...');
        $bar = $this->output->createProgressBar(count($screenshots));

        $cookieName = config('session.cookie', 'laravel_session');
        $cookieDomain = parse_url($baseUrl, PHP_URL_HOST) ?: 'nova.test';

        foreach ($screenshots as $shot) {
            $path = $outputDir . '/' . $shot['file'];
            try {
                $browser = Browsershot::url($shot['url'])
                    ->setNodeBinary(trim((string) shell_exec('which node')))
                    ->setNpmBinary(trim((string) shell_exec('which npm')))
                    ->useCookies([$cookieName => $sessionCookie], $cookieDomain)
                    ->windowSize($width, $height)
                    ->waitUntilNetworkIdle()
                    ->setDelay(2000)
                    ->dismissDialogs()
                    ->ignoreHttpsErrors()
                    ->noSandbox();

                if (! empty($shot['fullPage'])) {
                    $browser->fullPage();
                }

                if (! empty($shot['selector'])) {
                    $browser->select($shot['selector']);
                }

                $browser->save($path);

                $bar->advance();
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("  ⚠ {$shot['key']}: {$e->getMessage()}");
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Screenshots saved to: {$outputDir}");

        return self::SUCCESS;
    }

    private function getSessionCookie(User $user, string $guard): ?string
    {
        Auth::guard($guard)->login($user);

        $session = session();
        $session->start();
        $session->put("login_{$guard}_" . sha1(get_class(Auth::guard($guard)->getProvider())), $user->getKey());
        $session->save();

        $sessionId = $session->getId();
        Auth::guard($guard)->logout();

        return $sessionId ? encrypt($sessionId, false) : null;
    }

    /**
     * @return array<int, array{key: string, file: string, url: string, fullPage?: bool, selector?: string}>
     */
    private function getScreenshots(string $guard, string $baseUrl): array
    {
        $tenant = \App\Models\Team::first()?->slug ?? 'team';

        if ($guard === 'taxista') {
            return [
                ['key' => 'portal-dashboard', 'file' => 'taxista-dashboard.png', 'url' => "{$baseUrl}/portal", 'fullPage' => true],
                ['key' => 'portal-documentos', 'file' => 'taxista-documentos.png', 'url' => "{$baseUrl}/portal/taxista-documents"],
                ['key' => 'portal-citas', 'file' => 'taxista-citas.png', 'url' => "{$baseUrl}/portal/appointments"],
                ['key' => 'portal-tickets', 'file' => 'taxista-tickets.png', 'url' => "{$baseUrl}/portal/tickets"],
                ['key' => 'portal-gastos', 'file' => 'taxista-gastos.png', 'url' => "{$baseUrl}/portal/expenses"],
                ['key' => 'portal-taxis', 'file' => 'taxista-taxis.png', 'url' => "{$baseUrl}/portal/taxis"],
                ['key' => 'portal-tracking', 'file' => 'taxista-tracking.png', 'url' => "{$baseUrl}/portal/taxista-tracking"],
                ['key' => 'portal-chat', 'file' => 'taxista-chat.png', 'url' => "{$baseUrl}/portal/taxista-chats"],
                ['key' => 'portal-notas', 'file' => 'taxista-notas.png', 'url' => "{$baseUrl}/portal/notes"],
            ];
        }

        $t = "app/team/{$tenant}";

        return [
            ['key' => 'empleados-lista', 'file' => 'empleados-lista.png', 'url' => "{$baseUrl}/{$t}/employees", 'fullPage' => true],
            ['key' => 'cuadrante-turnos', 'file' => 'cuadrante-turnos.png', 'url' => "{$baseUrl}/{$t}/shift-roster", 'fullPage' => true],
            ['key' => 'metricas', 'file' => 'empleados-metricas.png', 'url' => "{$baseUrl}/{$t}/employee-metrics", 'fullPage' => true],
            ['key' => 'time-off-roster', 'file' => 'time-off-roster.png', 'url' => "{$baseUrl}/{$t}/time-off-roster", 'fullPage' => true],
            ['key' => 'taxistas-lista', 'file' => 'taxistas-lista.png', 'url' => "{$baseUrl}/{$t}/taxistas"],
            ['key' => 'taxista-docs', 'file' => 'taxista-documentos-tabla.png', 'url' => "{$baseUrl}/{$t}/taxista-documents"],
        ];
    }
}
