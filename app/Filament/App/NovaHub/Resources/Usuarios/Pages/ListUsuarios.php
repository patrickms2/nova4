<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\Usuarios\Pages;

use Filament\Support\Icons\Heroicon;

use App\Filament\App\NovaHub\Resources\Usuarios\UsuariosResource;
/* use App\Filament\Widgets\LocationStatsOverview; */
use App\Models\Taxi\Municipio;
use App\Models\Taxi\Usuario;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
/*use App\Filament\Widgets\LocationMapTableWidget;
use App\Filament\Widgets\LocationMapWidget;*/
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs\Tab;
/* use App\Filament\Pages\Actions\ImpersonatePageAction; */
/* use Archilex\AdvancedTables\Filters\UserSelectFilter; */
use Illuminate\Database\Eloquent\Builder;

final class ListUsuarios extends ListRecords
{
    // protected string $view = 'filament.resources.usuarios-resource.pages.list-usuarios';
    public Usuario $record;

    protected static string $resource = UsuariosResource::class;

    public static function getTableDefaultAction(): ?string
    {
        return 'edit';   // ← Acción por defecto al hacer clic en una fila
    }

    protected function getDefaultTableAction(): ?string
    {
        return 'edit';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // LocationMapWidget::class,
            // LocationStatsOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(),
        ];
    }

    protected function getTableFiltersFormWidth(): string
    {
        return 'xs';
    }

    /*    public function getTabs(): array
        {*/
    // Optimización: Obtener recuentos en una sola consulta
    /*$counts = \Illuminate\Support\Facades\DB::table('usuarios')
        ->select('tipo_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
        ->whereIn('tipo_id', [1, 2, 3, 4])
        ->groupBy('tipo_id')
        ->pluck('total', 'tipo_id')
        ->toArray();*/

    // Usar caché para la consulta total (5 minutos)
    /*$totalCount = \Illuminate\Support\Facades\Cache::remember('usuarios_total_count', now()->addMinutes(5), function () {
        return Usuario::whereHas('tipo')->count();
    });*/

    /*return [
        'TODOS' => Tab::make()
            ->icon(Heroicon::OutlinedUsers)
            ->badge($totalCount),

        'ADMIN' => Tab::make()
            ->icon(Heroicon::OutlinedUserCircle)
            ->badge($counts[3] ?? 0)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo_id', '=', 3)),

        'TAXISTAS' => Tab::make()
            ->icon(Heroicon::OutlinedTruck)
            ->badge($counts[4] ?? 0)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo_id', '=', 4)),

        'EMPLEADOS' => Tab::make()
            ->icon(Heroicon::OutlinedUserGroup)
            ->badge($counts[1] ?? 0)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo_id', '=', 1)),

        'HOTELES' => Tab::make()
            ->icon(Heroicon::OutlinedBuildingOffice)
            ->badge($counts[2] ?? 0)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo_id', '=', 2)),
        'TIAS' => Tab::make()
            ->icon(Heroicon::OutlinedRectangleStack)
            ->badge(Usuario::whereHas('municipio')->count())
            ->modifyQueryUsing(fn (Builder $query) => $query->where('municipio_id','=', 1)),*/

    /* ];
    }*/
}
