<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\Pages;

use App\Filament\App\Rentals\Resources\RentalReservationResource;
use App\Models\RentalReservation;
use Asmit\AdvancedKanban\Columns\KanbanColumn;
use Asmit\AdvancedKanban\Concerns\InteractsWithKanban;
use Asmit\AdvancedKanban\Contracts\HasKanban;
use Asmit\AdvancedKanban\Kanban;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KanbanRentalReservations extends Page implements HasKanban
{
    use InteractsWithKanban;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $resource = RentalReservationResource::class;

    protected string $view = 'advanced-kanban::index';

    protected static string $model = RentalReservation::class;

    protected static string $recordTitleAttribute = 'reference_code';

    protected static string $recordStatusAttribute = 'status';

    protected static ?string $title = 'Kanban de reservas';

    public function handleRecordMove(string $newStatus, Model $record): void
    {
        $allowed = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];

        if (! in_array($newStatus, $allowed, true)) {
            return;
        }

        if ($record instanceof RentalReservation) {
            $record->update(['status' => $newStatus]);
        }
    }

    public function kanban(Kanban $kanban): Kanban
    {
        return $kanban
            ->model(static::$model)
            ->statusField(static::$recordStatusAttribute)
            ->titleField(static::$recordTitleAttribute)
            ->descriptionField('channel')
            ->searchableFields(['reference_code', 'channel'])
            ->enableLoadingIndicator()
            ->recordsPerColumn(15)
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->with(['guest'])->orderByDesc('check_in');
            })
            ->columns([
                KanbanColumn::make('pending')
                    ->label('Pendiente')
                    ->iconcolor('warning')
                    ->modifyRecordQueryUsing(fn (Builder $query): Builder => $query->where('status', 'pending')),

                KanbanColumn::make('confirmed')
                    ->label('Confirmada')
                    ->iconcolor('info')
                    ->modifyRecordQueryUsing(fn (Builder $query): Builder => $query->where('status', 'confirmed')),

                KanbanColumn::make('checked_in')
                    ->label('Check-in')
                    ->iconcolor('success')
                    ->modifyRecordQueryUsing(fn (Builder $query): Builder => $query->where('status', 'checked_in')),

                KanbanColumn::make('checked_out')
                    ->label('Check-out')
                    ->iconcolor('gray')
                    ->modifyRecordQueryUsing(fn (Builder $query): Builder => $query->where('status', 'checked_out')),

                KanbanColumn::make('cancelled')
                    ->label('Cancelada')
                    ->iconcolor('danger')
                    ->modifyRecordQueryUsing(fn (Builder $query): Builder => $query->where('status', 'cancelled')),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendar')
                ->label('Calendario')
                ->icon('heroicon-o-calendar-days')
                ->url(RentalReservationResource::getUrl('calendar')),
            Action::make('table')
                ->label('Listado')
                ->icon('heroicon-o-table-cells')
                ->url(RentalReservationResource::getUrl('index')),
        ];
    }
}
