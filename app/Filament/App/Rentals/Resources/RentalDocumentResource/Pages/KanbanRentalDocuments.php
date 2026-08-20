<?php

namespace App\Filament\App\Rentals\Resources\RentalDocumentResource\Pages;

use App\Filament\App\Rentals\Resources\RentalDocumentResource;
use App\Models\RentalDocument;
use Asmit\AdvancedKanban\Concerns\InteractsWithKanban;
use Asmit\AdvancedKanban\Columns\KanbanColumn;
use Asmit\AdvancedKanban\Contracts\HasKanban;
use Asmit\AdvancedKanban\Kanban;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KanbanRentalDocuments extends Page implements HasKanban
{
    use InteractsWithKanban;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $resource = RentalDocumentResource::class;

    protected string $view = 'advanced-kanban::index';

    protected static string $model = \App\Models\RentalDocument::class;

    protected static string $recordTitleAttribute = 'title';

    protected static string $recordStatusAttribute = 'category';

    protected static ?string $title = 'Kanban de documentos';

    public function handleRecordMove(string $newStatus, Model $record): void
    {
        $allowed = array_keys(RentalDocument::categories());

        if (! in_array($newStatus, $allowed, true)) {
            return;
        }

        if ($record instanceof RentalDocument) {
            $record->update(['category' => $newStatus]);
        }
    }

    public function kanban(Kanban $kanban): Kanban
    {
        $columns = collect(RentalDocument::categories())
            ->map(function (string $label, string $key): KanbanColumn {
                return KanbanColumn::make($key)
                    ->label($label)
                    ->modifyRecordQueryUsing(fn (Builder $query): Builder => $query->where('category', $key));
            })
            ->values()
            ->all();

        return $kanban
            ->model(static::$model)
            ->statusField(static::$recordStatusAttribute)
            ->titleField(static::$recordTitleAttribute)
            ->descriptionField('expiry_date')
            ->searchableFields(['title'])
            ->enableLoadingIndicator()
            ->recordsPerColumn(15)
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->with(['documentable'])->orderByDesc('updated_at');
            })
            ->columns($columns);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('table')
                ->label('Listado')
                ->icon('heroicon-o-table-cells')
                ->url(RentalDocumentResource::getUrl('index')),
        ];
    }
}
