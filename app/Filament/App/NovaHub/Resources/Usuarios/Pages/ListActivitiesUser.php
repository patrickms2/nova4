<?php

namespace App\Filament\App\NovaHub\Resources\Usuarios\Pages;

use App\Filament\App\NovaHub\Resources\Usuarios\UsuariosResource;
use App\Models\Usuario;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ListActivitiesUser extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = UsuariosResource::class;
    protected string $view = 'filament.resources.usuarios-resource.pages.list-citas-usuarios';
    public Usuario $record;

    public function getBreadcrumb(): ?string
    {
        return __('List Record Activities');
    }

    public function getTitle(): string
    {
        return __('List Record Activities');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->where([
                ['subject_type', '=', Usuario::class],
                ['subject_id', '=', $this->record->id],
            ]))
            ->columns($this->getTableColumns())
            ->filters($this->getTableFilters())
            ->actions($this->getTableActions());
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')
                ->searchable()
                ->toggleable()
                ->translateLabel(),
            TextColumn::make('log_name')
                ->searchable()
                ->toggleable()
                ->translateLabel(),
            TextColumn::make('event')
                ->searchable()
                ->toggleable()
                ->translateLabel(),
            TextColumn::make('subject_type')
                ->label('Subject')
                ->toggleable()
                ->translateLabel()
                ->description(function (Activity $record) {
                    return $record->subject_id;
                }),
            TextColumn::make('created_at'),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('log_name')
                ->options([
                    'Resource' => 'Resource',
                    'Access' => 'Access',
                ])
                ->searchable(),
            SelectFilter::make('event')
                ->options([
                    'Created' => 'Created',
                    'Updated' => 'Updated',
                    'Login' => 'Login',
                ])
                ->indicateUsing()
                ->searchable(),
            Filter::make('created_at')
                ->schema([
                    DatePicker::make('created_from')->label(trans('From Date')),
                    DatePicker::make('created_until')->label(trans('To Date'))->default(now()),
                ])
                ->indicateUsing()
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->schema(function () {
                return [
                    TextInput::make('id'),
                    TextInput::make('log_name'),
                    TextInput::make('event'),
                    TextInput::make('description'),
                    TextInput::make('subject_type'),
                    TextInput::make('subject_id'),
                    TextInput::make('causer_type'),
                    TextInput::make('created_at'),
                    TextInput::make('updated_at'),
                ];
            }),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('id'),
            TextInput::make('log_name'),
        ];
    }
}
