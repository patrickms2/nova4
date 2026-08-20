<?php

namespace App\Filament\App\Community\Resources\CommunityPlans\Pages;

use App\Actions\Community\ResolveIncident;
use App\Filament\App\Community\Resources\CommunityPlans\CommunityPlanResource;
use App\Models\Incident;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification; 

class ManagePlanIncidents extends ManageRelatedRecords
{
    protected static string $resource = CommunityPlanResource::class;

    protected static string $relationship = 'incidents';

    protected static ?string $title = 'Incidencias';

    protected static ?string $navigationLabel = 'Incidencias';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Incidencia')->schema([

                Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload()->required(),
                Select::make('work_order_id')->label('Orden de trabajo')->relationship('workOrder', 'code')->searchable()->preload(),
                Select::make('work_order_task_id')->label('Tarea')->relationship('workOrderTask', 'title')->searchable()->preload(),

                TextInput::make('title')->label('Título')->required()->columnSpanFull(),
                Textarea::make('description')->label('Descripción')->required()->columnSpanFull(),
                Select::make('priority')->label('Prioridad')->options(self::priorities())->default('normal')->required(),
                Select::make('status')->label('Estado')->options(self::statuses())->default('open')->required(),
                Textarea::make('resolution_note')->label('Resolución')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {

        return $table->defaultSort('created_at', 'desc')->columns([TextColumn::make('title')->label('Incidencia')->searchable()->wrap(), TextColumn::make('community.name')->label('Comunidad')->searchable(), TextColumn::make('workOrder.code')->label('Orden')->placeholder('—'),
            TextColumn::make('priority')->label('Prioridad')->badge(),
            TextColumn::make('status')->label('Estado')->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'open' => 'Abierta',
                    'in_progress' => 'Resolviendo',
                    'resolved' => 'Resuelta',
                    default => 'Sin estado',
                })
                ->color(fn (string $state): string => match ($state) {
                    'open' => 'warning',
                    'in_progress' => 'danger',
                    'resolved' => 'success',
                    default => 'gray',
                }),

            TextColumn::make('created_at')->label('Fecha')->dateTime()->sortable(), TextColumn::make('comments_count')->label('Comentarios')->counts('comments')])->filters([SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload(), SelectFilter::make('priority')->options(self::priorities()), SelectFilter::make('status')->options(self::statuses()), Filter::make('open')->label('Abiertas')->query(fn (Builder $query): Builder => $query->whereNotIn('status', ['resolved', 'closed'])), Filter::make('with_order')->label('Con orden')->query(fn (Builder $query): Builder => $query->whereNotNull('work_order_id'))])->recordActions([Action::make('resolve')->label('Resolver')->icon('heroicon-o-check-circle')->color('success')->visible(fn (Incident $record): bool => ! in_array($record->status, ['resolved', 'closed'], true))->requiresConfirmation()->schema([Textarea::make('note')->label('Nota de resolución')])->action(function (Incident $record, array $data): void {
                app(ResolveIncident::class)->handle($record, 'resolved', auth()->id(), $data['note'] ?? null);
                Notification::make()->title('Incidencia resuelta')->success()->send();
            })])
            ->headerActions([CreateAction::make()]);

    }

    private static function priorities(): array
    {
        return ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
    }

    private static function statuses(): array
    {
        return ['open' => 'Abierta', 'in_progress' => 'En curso', 'resolved' => 'Resuelta', 'closed' => 'Cerrada'];
    }
}
