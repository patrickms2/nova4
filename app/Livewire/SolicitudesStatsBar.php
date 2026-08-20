<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\ServicioQueryService;
use Livewire\Attributes\On;
use Livewire\Component;

class SolicitudesStatsBar extends Component
{
    /** @var array<string, array{hoy: int, mes: int}> */
    public array $stats = [];

    public int $totalHoy = 0;

    public int $totalMes = 0;

    public bool $loading = true;

    public string $lastUpdated = '';

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $this->loading = true;

        $result = app(ServicioQueryService::class)->statsPorMunicipio();

        $this->stats = $result['stats'];
        $this->totalHoy = $result['totalHoy'];
        $this->totalMes = $result['totalMes'];
        $this->lastUpdated = now()->format('H:i:s');
        $this->loading = false;
    }

    #[On('solicitud-taxi-recibida')]
    public function onSolicitudRecibida(): void
    {
        $this->loadStats();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.solicitudes-stats-bar');
    }
}
