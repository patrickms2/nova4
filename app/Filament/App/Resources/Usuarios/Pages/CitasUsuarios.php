<?php

namespace App\Filament\App\Resources\Usuarios\Pages;

use App\Filament\App\Resources\Usuarios\UsuariosResource;
use App\Filament\Widgets\LocationMapTableWidget;
use App\Filament\Widgets\LocationMapWidget;
use App\Filament\Widgets\LocationStatsOverview;
use App\Models\Cita;
use App\Models\Municipio;
use App\Models\TiposUsuario;
use App\Models\TipoUsuario;
use App\Models\Usuario;
use AymanAlhattami\FilamentPageWithSidebar\FilamentPageSidebar;
use AymanAlhattami\FilamentPageWithSidebar\PageNavigationItem;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords\Tab;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class CitasUsuarios extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = UsuariosResource::class;
    protected string $view = 'filament.resources.usuarios-resource.pages.list-usuarios';
    public Usuario $record;

    protected function getTableQuery(): Builder
    {
        return Cita::query()->with("usuario")
            ->where([['usuario_id', '=', $this->record->id]]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //LocationMapWidget::class,
            //LocationStatsOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
        ];
    }

    /*protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->select([
                'id', 'nombre', 'appointment_date', 'estado_id', 'usuario_id', 'created_at', 'updated_at'
            ])
            ->with([
                'estado:id,nombre',
                'usuario:id,nombre'
            ])
            ->where([
                ['usuario_id', '=', $this->record->id],
            ]);;
    }*/

    protected function getTableFiltersFormWidth(): string
    {
        return 'xs';
    }

    public function getTabs(): array
    {
        // Optimización: Obtener recuentos en una sola consulta
        $counts = \Illuminate\Support\Facades\DB::table('usuarios')
            ->select('tipo_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->whereIn('tipo_id', [1, 2, 3, 4])
            ->groupBy('tipo_id')
            ->pluck('total', 'tipo_id')
            ->toArray();

        // Usar caché para la consulta total (5 minutos)
        $totalCount = \Illuminate\Support\Facades\Cache::remember('usuarios_total_count', now()->addMinutes(5), function () {
            return Usuario::whereHas('tipo')->count();
        });

        return [
            'TODOS' => Tab::make()
                ->icon('heroicon-o-users')
                ->badge($totalCount),

            'ADMIN' => Tab::make()
                ->icon('heroicon-o-user-circle')
                ->badge($counts[3] ?? 0)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('tipo_id', '=', 3)),

            'TAXISTAS' => Tab::make()
                ->icon('heroicon-o-truck')
                ->badge($counts[4] ?? 0)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('tipo_id', '=', 4)),

            'EMPLEADOS' => Tab::make()
                ->icon('heroicon-o-user-group')
                ->badge($counts[1] ?? 0)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('tipo_id', '=', 1)),

            'HOTELES' => Tab::make()
                ->icon('heroicon-o-building-office')
                ->badge($counts[2] ?? 0)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('tipo_id', '=', 2)),
            /*'TIAS' => Tab::make()
                ->icon('heroicon-o-rectangle-stack')
                ->badge(Usuario::whereHas('municipio')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('municipio_id','=', 1)),*/

        ];
    }
}
