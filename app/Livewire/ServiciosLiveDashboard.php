<?php

namespace App\Livewire;

use App\Services\ServicioQueryService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ServiciosLiveDashboard extends Component
{
    public string $filtroEstado = '2';
    
    public ?int $filtroHotel = null;
    
    public ?string $fechaDesde = null;
    
    public ?string $fechaHasta = null;

    public int $page = 1;

    public int $pageSize = 20;

    /** @var array<int, array<string, mixed>> */
    public array $servicios = [];

    public int $totalPend = 0;

    public int $totalTotal = 0;

    public bool $loading = true;

    public string $lastUpdated = '';

    /** @var array<int, array{id: int, nombre: string}> */
    public array $hoteles = [];

    public function mount(): void
    {
        $this->loadHoteles();
        $this->loadServicios();
    }

    public function loadServicios(): void
    {
        $this->loading = true;

        $service = app(ServicioQueryService::class);
        $result = $service->listar(
            $this->filtroEstado ?: '', 
            $this->page, 
            $this->pageSize,
            $this->filtroHotel,
            $this->fechaDesde,
            $this->fechaHasta
        );

        $this->servicios = $result['data'];
        $this->totalPend = $result['total_pend'];
        $this->totalTotal = $result['total_total'];

        $this->lastUpdated = now()->format('H:i:s');
        $this->loading = false;
    }

    public function loadHoteles(): void
    {
        $service = app(ServicioQueryService::class);
        $this->hoteles = $service->getHotelesDisponibles();
    }

    public function setFiltroEstado(string $estado): void
    {
        $this->filtroEstado = $this->filtroEstado === $estado ? '' : $estado;
        $this->page = 1;
        $this->loadServicios();
    }

    public function setFiltroHotel(?int $hotel): void
    {
        $this->filtroHotel = $this->filtroHotel === $hotel ? null : $hotel;
        $this->page = 1;
        $this->loadServicios();
    }

    public function setFiltroFechaDesde(?string $fecha): void
    {
        $this->fechaDesde = $fecha;
        $this->page = 1;
        $this->loadServicios();
    }

    public function setFiltroFechaHasta(?string $fecha): void
    {
        $this->fechaHasta = $fecha;
        $this->page = 1;
        $this->loadServicios();
    }

    public function limpiarFiltros(): void
    {
        $this->filtroEstado = '2';
        $this->filtroHotel = null;
        $this->fechaDesde = null;
        $this->fechaHasta = null;
        $this->page = 1;
        $this->loadServicios();
    }

    public function nextPage(): void
    {
        $this->page++;
        $this->loadServicios();
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadServicios();
        }
    }

    public function refresh(): void
    {
        $this->loadServicios();
    }

    #[On('solicitud-taxi-recibida')]
    public function onSolicitudRecibida(): void
    {
        $this->loadServicios();
    }

    #[Computed]
    public function totalPages(): int
    {
        return (int) ceil($this->totalTotal / $this->pageSize);
    }

    public function getEstadoColor(string $estado): string
    {
        return match ($estado) {
            'SOLICITADO' => 'warning',
            'TRAMITADO' => 'success',
            'CANCELADO' => 'danger',
            'RESERVADO' => 'info',
            'NO ATENDIDO' => 'warning',
            default => 'gray',
        };
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.servicios-live-dashboard');
    }
}
