<?php

declare(strict_types=1);

namespace App\NativeComponents\StaffAccess;

use App\Services\Staff\StaffApiClient;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;
use Throwable;

class DashboardScreen extends NativeComponent
{
    public array $grants = [];

    public ?array $session = null;

    public ?int $selectedGrantId = null;

    public ?int $selectedPointId = null;

    public ?string $error = null;

    public bool $loading = false;

    public function mount(): void
    {
        if (app(StaffApiClient::class)->getToken() === null) {
            $this->replace('/staff/login');

            return;
        }

        $this->loadState();
    }

    public function loadState(): void
    {
        $api = app(StaffApiClient::class);
        $this->loading = true;
        $this->error = null;

        try {
            $this->grants = $api->grants();
            $this->session = $api->currentSession();

            if ($this->session !== null) {
                $this->selectedGrantId = $this->session['access_grant_id'];
                $this->selectedPointId = $this->session['access_point_id'];
            } elseif ($this->selectedGrantId === null && ! empty($this->grants)) {
                $this->selectedGrantId = $this->grants[0]['id'];
            }
        } catch (Throwable $exception) {
            $this->error = 'No se pudo cargar el estado. Comprueba la conexión.';
        } finally {
            $this->loading = false;
        }
    }

    public function startSession(): void
    {
        $api = app(StaffApiClient::class);
        if ($this->selectedGrantId === null || $this->selectedPointId === null) {
            $this->error = 'Selecciona una propiedad y un punto de acceso.';

            return;
        }

        $this->loading = true;
        $this->error = null;

        try {
            $result = $api->startSession([
                'access_grant_id' => $this->selectedGrantId,
                'access_point_id' => $this->selectedPointId,
            ]);

            $this->session = $result['session'];
        } catch (Throwable $exception) {
            $this->error = 'No se pudo iniciar el acceso. Verifica horario y permisos.';
        } finally {
            $this->loading = false;
        }
    }

    public function finishSession(): void
    {
        $api = app(StaffApiClient::class);
        if ($this->session === null) {
            return;
        }

        $this->loading = true;
        $this->error = null;

        try {
            $result = $api->finishSession($this->session['id']);
            $this->session = $result['session'];

            if (! empty($result['report_required'])) {
                $this->navigate('/staff/report/'.$this->session['id']);

                return;
            }
        } catch (Throwable $exception) {
            $this->error = 'No se pudo finalizar la sesión.';
        } finally {
            $this->loading = false;
        }
    }

    public function completeSession(): void
    {
        $api = app(StaffApiClient::class);
        if ($this->session === null) {
            return;
        }

        $this->loading = true;
        $this->error = null;

        try {
            $result = $api->completeSession($this->session['id']);
            $this->session = $result['session'];
        } catch (Throwable $exception) {
            $this->error = 'No se pudo completar la salida.';
        } finally {
            $this->loading = false;
        }
    }

    public function logout(): void
    {
        app(StaffApiClient::class)->clearToken();
        $this->replace('/staff/login');
    }

    public function render(): View
    {
        return view('native.staff-access.dashboard-screen');
    }
}
