<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Pagos;

use App\Filament\App\Resources\Pagos\Pages\CreatePago;
use App\Filament\App\Resources\Pagos\Pages\EditPago;
use App\Filament\App\Resources\Pagos\Pages\ListPagos;
use App\Filament\Support\baseresource;
use App\Models\Taxi\Pago;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Admin\Clusters\Pagos\PagoResource as ClusterPagoResource;
use App\Services\PagoServicioRefService;

class PagoResource extends baseresource
{
    protected static ?string $model = Pago::class;

    protected static bool $isScopedToTenant = false;

    protected static bool $isGloballySearchable = true;

    protected static string|\UnitEnum|null $navigationGroup = 'Departamentos';

    protected static ?string $navigationLabel = 'Pagos';

    protected static ?int $navigationSort = 10;
    
    protected static bool $shouldRegisterNavigation = true;

    public static function form(Form $form): Form
    {
        return ClusterPagoResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return ClusterPagoResource::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPagos::route('/'),
            //'create' => CreatePago::route('/create'),
            //'edit' => EditPago::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['referencia', 'nombre', 'telefono', 'status'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Cliente' => (string)($record->nombre ?? '-'),
            'Referencia' => (string)($record->referencia ?? '-'),
        ];
    }
}
