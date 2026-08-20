<?php

namespace App\Filament\App\Community\Resources\WorkOrders\Pages;

use App\Filament\App\Community\Resources\WorkOrders\WorkOrderResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Models\WorkOrder;
use App\Actions\Community\TransitionWorkOrder;
use App\Models\CommunityPlan;
use App\Filament\App\Community\Actions\GeneratePlanWorkOrdersAction;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Schemas\Components\Section;

class ManagePlans extends ManageRelatedRecords
{
    protected static string $relationship = 'plan';

    protected static ?string $title = 'Planes';
    protected static string $resource = WorkOrderResource::class;
    protected static ?string $navigationLabel = 'Plan Items';

    private static function transition2(WorkOrder $record, string $status): void
    {
        app(TransitionWorkOrder::class)->handle($record, $status, auth()->id());
        Notification::make()->title('Orden actualizada')->success()->send();
    }

    private static function transitionActions(): array
    {
        return [
            ViewAction::make(),
            EditAction::make(),
            DeleteAction::make(),
               ];
    }

    public static function statuses(): array
    {
        return ['pending' => 'Pendiente', 'in_progress' => 'En curso', 'finished' => 'Finalizada', 'cancelled' => 'Cancelada'];
    }

    public function form(Schema $schema): Schema
    {
  return $schema->components([Section::make('Plan de mantenimiento')->schema([Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload()->required(),
            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            TextInput::make('description')->label('Descripción')->maxLength(255)->columnSpanFull(),
            DatePicker::make('valid_from')->label('Válido desde')->required(), DatePicker::make('valid_until')->label('Válido hasta')->afterOrEqual('valid_from'), Select::make('status')->label('Estado')->options(['draft' => 'Borrador', 'active' => 'Activo', 'inactive' => 'Inactivo', 'replaced' => 'Sustituido'])->default('draft')->required()])->columns(2)]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('valid_from', 'desc')->columns([TextColumn::make('community.name')->label('Comunidad')->searchable()->sortable(), TextColumn::make('name')->label('Nombre'), TextColumn::make('valid_from')->label('Desde')->date()->sortable(), TextColumn::make('valid_until')->label('Hasta')->date()->placeholder('Sin fin'), TextColumn::make('items_count')->label('Tareas')->counts('items'), TextColumn::make('status')->label('Estado')->badge()])->filters([SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload(), SelectFilter::make('status')->options(['draft' => 'Borrador', 'active' => 'Activo', 'inactive' => 'Inactivo', 'replaced' => 'Sustituido'])])
        ->recordActions([
                GeneratePlanWorkOrdersAction::make(),
            EditAction::make('Editar'),
            DeleteAction::make('Eliminar'),
        ])->headerActions([
            CreateAction::make(),
        ]);
    }
}
