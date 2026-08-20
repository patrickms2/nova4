<?php

namespace App\Filament\App\Rentals\Resources\RentalExpenseResource\Pages;

use App\Filament\App\Rentals\Resources\RentalExpenseResource;
use App\Models\RentalExpense;
use Asmit\AdvancedKanban\Concerns\InteractsWithKanban;
use Asmit\AdvancedKanban\Columns\KanbanColumn;
use Asmit\AdvancedKanban\Contracts\HasKanban;
use Asmit\AdvancedKanban\Kanban;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KanbanRentalExpenses extends Page implements HasKanban
{
    use InteractsWithKanban;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $resource = RentalExpenseResource::class;
    protected static string $model = \App\Models\RentalExpense::class;
    protected static string $recordTitleAttribute = 'description';
    protected static string $recordStatusAttribute = 'status';
    protected static ?string $title = 'Kanban de gastos';
    protected string $view = 'advanced-kanban::index';

    public function handleRecordMove(string $newStatus, Model $record): void
    {
        $allowed = ['pending', 'paid', 'cancelled'];

        if (!in_array($newStatus, $allowed, true)) {
            return;
        }

        if ($record instanceof RentalExpense) {
            $record->update(['status' => $newStatus]);
        }
    }

    public function kanban(Kanban $kanban): Kanban
    {
        return $kanban
            ->model(static::$model)
            ->statusField(static::$recordStatusAttribute)
            ->titleField(static::$recordTitleAttribute)
            ->descriptionField('provider_name')
            ->searchableFields(['description', 'provider_name'])
            ->enableLoadingIndicator()
            ->recordsPerColumn(15)
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->orderByDesc('expense_date');
            })
            ->columns([
                KanbanColumn::make('pending')
                    ->label('Pendiente')
                    ->iconcolor('warning')
                    ->modifyRecordQueryUsing(fn(Builder $query): Builder => $query->where('status', 'pending')),

                KanbanColumn::make('paid')
                    ->label('Pagado')
                    ->iconcolor('success')
                    ->modifyRecordQueryUsing(fn(Builder $query): Builder => $query->where('status', 'paid')),

                KanbanColumn::make('cancelled')
                    ->label('Cancelado')
                    ->iconcolor('danger')
                    ->modifyRecordQueryUsing(fn(Builder $query): Builder => $query->where('status', 'cancelled')),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('table')
                ->label('Listado')
                ->icon('heroicon-o-table-cells')
                ->url(RentalExpenseResource::getUrl('index')),
        ];
    }
}
