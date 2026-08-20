<?php

namespace App\Filament\App\Resources\Hoteles\Pages;

use App\Filament\App\Resources\Hoteles\HotelesResource;
use App\Models\Taxi\UsuarioDireccion;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Filament\Schemas\Components\Tabs\Tab;

class MapaHoteles extends ListRecords
{

    protected static string $resource = HotelesResource::class;
    protected static ?string $title = 'Hoteles';

    public static function getDisplayTitle(): ?string
    {
        return self::$title;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function mount(): void
    {

    }


    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }

    public function getTabs(): array
    {
        // Obtener todos los conteos en una sola consulta para mejorar rendimiento
        $counts = UsuarioDireccion::query()
            ->join('users', 'user.id', '=', 'usuarios_direcciones.user_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN users.municipio_id = 1 THEN 1 ELSE 0 END) as tias_count')
            ->selectRaw('SUM(CASE WHEN users.municipio_id = 3 THEN 1 ELSE 0 END) as yaiza_count')
            ->selectRaw('SUM(CASE WHEN users.municipio_id = 5 THEN 1 ELSE 0 END) as teguise_count')
            ->selectRaw('SUM(CASE WHEN users.status = 3 THEN 1 ELSE 0 END) as bloqueados_count')
            ->first();

        return [
            'TODOS' => Tab::make()
                ->icon('heroicon-o-rectangle-stack')
                ->badge($counts->total),

            'TIAS' => Tab::make()
                ->icon('heroicon-o-rectangle-stack')
                ->badge($counts->tias_count)
                ->query(fn(EloquentBuilder $query) => $query->join('users', 'users.id', '=', 'usuarios_direcciones.user_id')
                    ->where('users.municipio_id', 1)),

            'YAIZA' => Tab::make()
                ->icon('heroicon-o-rectangle-stack')
                ->badge($counts->yaiza_count)
                ->modifyQueryUsing(fn(EloquentBuilder $query) => $query
                    ->join('users', 'users.id', '=', 'usuarios_direcciones.user_id')
                    ->where('users.municipio_id', 2)),

            'TEGUISE' => Tab::make()
                ->icon('heroicon-o-rectangle-stack')
                ->badge($counts->teguise_count)
                ->modifyQueryUsing(fn(EloquentBuilder $query) => $query->where('users.municipio_id', 4)),

            'BLOQUEADO' => Tab::make()
                ->icon('heroicon-o-rectangle-stack')
                ->badge($counts->bloqueados_count)
                ->modifyQueryUsing(fn(EloquentBuilder $query) => $query->where('users.status', 3)),
        ];
    }

}
