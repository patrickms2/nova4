<?php

namespace Tests\Feature\Staff;

use App\Services\Staff\StaffApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Mobile\Testing\Native;
use Tests\TestCase;

class StaffNativeAppTest extends TestCase
{
    use RefreshDatabase;

    private function fakeApiClient(array $grants = [], ?array $session = null): StaffApiClient
    {
        return new readonly class('', $grants, $session) extends StaffApiClient
        {
            public function __construct(
                string $baseUrl,
                private array $grants,
                private ?array $session,
            ) {
                parent::__construct($baseUrl);
            }

            public function getToken(): ?string
            {
                return 'fake-token';
            }

            public function grants(): array
            {
                return $this->grants;
            }

            public function currentSession(): ?array
            {
                return $this->session;
            }

            public function startSession(array $payload): array
            {
                return ['session' => array_merge($this->session ?? [], $payload)];
            }

            public function finishSession(int $sessionId): array
            {
                return ['session' => $this->session ?? ['id' => $sessionId]];
            }

            public function completeSession(int $sessionId): array
            {
                return ['session' => array_merge($this->session ?? [], ['id' => $sessionId, 'status' => 'finished'])];
            }

            public function submitReport(int $sessionId, ?string $voicePath, array $photos = []): array
            {
                return ['success' => true];
            }
        };
    }

    public function test_dashboard_shows_start_button_when_no_active_session(): void
    {
        $client = $this->fakeApiClient([
            [
                'id' => 1,
                'name' => 'Propiedad A',
                'access_points' => [
                    ['id' => 10, 'name' => 'Puerta principal'],
                ],
            ],
        ]);

        app()->instance(StaffApiClient::class, $client);

        Native::visit('/staff/dashboard')
            ->assertSee('ENTRAR')
            ->assertSee('Selecciona acceso');
    }

    public function test_dashboard_shows_finish_button_during_active_session(): void
    {
        $client = $this->fakeApiClient([], [
            'id' => 5,
            'status' => 'active',
            'status_label' => 'En curso',
            'elapsed_seconds' => 120,
            'access_grant_id' => 1,
            'access_point_id' => 10,
        ]);

        app()->instance(StaffApiClient::class, $client);

        Native::visit('/staff/dashboard')
            ->assertSee('TERMINAR')
            ->assertSee('En curso');
    }

    public function test_report_screen_renders_media_controls(): void
    {
        $client = $this->fakeApiClient([], [
            'id' => 5,
            'status' => 'report_pending',
            'report_required' => true,
            'voice_required' => false,
            'photo_required' => true,
            'minimum_photos' => 1,
        ]);

        app()->instance(StaffApiClient::class, $client);

        Native::visit('/staff/report/5')
            ->assertSee('Grabar nota de voz')
            ->assertSee('Añadir foto')
            ->assertSee('Enviar parte');
    }
}
