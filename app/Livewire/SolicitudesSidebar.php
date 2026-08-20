<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\ServicioQueryService;
use Livewire\Attributes\On;
use Livewire\Component;

class SolicitudesSidebar extends Component
{
    public string $filtroEstado = '1';

    /** @var array<int, array<string, mixed>> */
    public array $servicios = [];

    public bool $loading = true;

    public function mount(): void
    {
        $this->loadSidebar();
    }

    public function loadSidebar(): void
    {
        $this->loading = true;

        $this->servicios = app(ServicioQueryService::class)->paraSidebar($this->filtroEstado);

        $this->loading = false;
    }

    public function setFiltro(string $estado): void
    {
        $this->filtroEstado = $this->filtroEstado === $estado ? '' : $estado;
        $this->loadSidebar();
    }

    #[On('solicitud-taxi-recibida')]
    public function onSolicitudRecibida(): void
    {
        $this->loadSidebar();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.solicitudes-sidebar');
    }
}
