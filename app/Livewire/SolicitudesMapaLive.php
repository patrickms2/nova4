<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\ServicioQueryService;
use Livewire\Attributes\On;
use Livewire\Component;

class SolicitudesMapaLive extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $markers = [];

    public int $totalSolicitudes = 0;

    public string $lastUpdated = '';

    public bool $loading = true;

    public function mount(): void
    {
        $this->loadMapData();
    }

    public function loadMapData(): void
    {
        $this->loading = true;

        $result = app(ServicioQueryService::class)->markersParaMapa();

        $this->markers = $result['markers'];
        $this->totalSolicitudes = $result['totalSolicitudes'];
        $this->lastUpdated = now()->format('H:i:s');
        $this->loading = false;
    }

    #[On('solicitud-taxi-recibida')]
    public function onSolicitudRecibida(): void
    {
        $this->loadMapData();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.solicitudes-mapa-live');
    }
}
