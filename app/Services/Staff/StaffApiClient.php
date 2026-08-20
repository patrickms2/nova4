<?php

declare(strict_types=1);

namespace App\Services\Staff;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Projectmata\MobileSecureStorage\Facades\SecureStorage;

/**
 * API client used by the NativePHP Staff mobile app.
 *
 * Stores the Sanctum token in the device keychain and delegates every access
 * decision to the NOVA backend. No privileged credentials or business rules
 * live on the device.
 */
readonly class StaffApiClient
{
    public function __construct(
        private string $baseUrl,
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('services.staff_api.url', config('app.url')));
    }

    public function setToken(?string $token): void
    {
        if ($token === null) {
            SecureStorage::removeItem('staff_api_token');

            return;
        }

        SecureStorage::setItem('staff_api_token', $token);
    }

    public function getToken(): ?string
    {
        $stored = SecureStorage::getItem('staff_api_token');

        return $stored['value'] ?? null;
    }

    public function clearToken(): void
    {
        SecureStorage::removeItem('staff_api_token');
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    public function login(array $credentials): array
    {
        $response = $this->request()->post('/api/staff/login', $credentials);

        $response->throw();

        $data = $response->json();

        if (! empty($data['token'])) {
            $this->setToken($data['token']);
        }

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function grants(): array
    {
        return $this->get('/api/staff/grants')['grants'] ?? [];
    }

    public function currentSession(): ?array
    {
        return $this->get('/api/staff/sessions/current')['session'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function startSession(array $payload): array
    {
        return $this->post('/api/staff/sessions', $payload);
    }

    public function finishSession(int $sessionId): array
    {
        return $this->post("/api/staff/sessions/{$sessionId}/finish", []);
    }

    /**
     * @param  array<int, string>  $photos
     */
    public function submitReport(int $sessionId, ?string $voicePath, array $photos = []): array
    {
        $request = $this->request();

        if ($voicePath !== null && is_file($voicePath)) {
            $request->attach('voice', fopen($voicePath, 'r'), basename($voicePath));
        }

        foreach ($photos as $index => $photoPath) {
            if (is_file($photoPath)) {
                $request->attach("photos[{$index}]", fopen($photoPath, 'r'), basename($photoPath));
            }
        }

        $response = $request->post("{$this->baseUrl}/api/staff/sessions/{$sessionId}/report");
        $response->throw();

        return $response->json();
    }

    public function completeSession(int $sessionId): array
    {
        return $this->post("/api/staff/sessions/{$sessionId}/complete", []);
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $path): array
    {
        $response = $this->request()->get("{$this->baseUrl}{$path}");
        $response->throw();

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        $response = $this->request()->post("{$this->baseUrl}{$path}", $payload);
        $response->throw();

        return $response->json() ?? [];
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(30);

        $token = $this->getToken();

        if ($token !== null) {
            $request->withToken($token);
        }

        return $request;
    }
}
