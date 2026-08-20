<?php

namespace App\Filament\App\NovaHub\Resources\Usuarios\Pages;

use Filament\Support\Icons\Heroicon;

use App\Filament\App\NovaHub\Resources\Citas\CitaResource;
use App\Filament\App\NovaHub\Resources\Usuarios\UsuariosResource;
use App\Filament\Widgets\LocationStatsOverview;
use App\Models\Cita;
use App\Models\Municipio;
use App\Models\Usuario;

use App\Models\TipoUsuario;
use App\Models\TiposUsuario;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Widgets\LocationMapTableWidget;
use App\Filament\Widgets\LocationMapWidget;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Resource;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use AymanAlhattami\FilamentPageWithSidebar\FilamentPageSidebar;
use AymanAlhattami\FilamentPageWithSidebar\PageNavigationItem;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Concerns\HasLineClamp;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Illuminate\Support\Facades\Hash;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;

class CitasUsuarios extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = UsuariosResource::class;
    protected string $view = 'filament.resources.usuarios-resource.pages.list-usuarios';
    public Usuario $record;

    public function table(Table $table): Table
    {
        return $table
            ->query(Cita::query()->with('usuario')
                ->where([['usuario_id', '=', $this->record->id]]));
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
            /*'TIAS' => Tab::make()
                ->icon(Heroicon::OutlinedRectangleStack)
                ->badge(Usuario::whereHas('municipio')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('municipio_id','=', 1)),*/

        ];
    }
}
