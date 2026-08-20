<?php

namespace App\Filament\App\NovaHub\Resources\Pagos\Pages;

use App\Filament\App\NovaHub\Resources\Pagos\PagoResource;
use App\Models\Taxi\PagoServicio;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditPago extends EditRecord
{
    protected static string $resource = PagoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    #[On('refreshPagos')]
    public function refresh(): void
    {
        $this->record->pagado = PagoServicio::where('pago_id', $this->record->id)->sum('importe');
    }
}
