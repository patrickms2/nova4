<?php

namespace App\Filament\App\Community\Support;

use App\Models\WorkCatalog;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;

class PlanItemForm
{
    public static function components(): array
    {
        return [
            Select::make('work_catalog_id')
                ->label('Servicio')
                ->relationship('catalog', 'title', fn (Builder $query): Builder => $query->with('category')->where('active', true))
                ->getOptionLabelFromRecordUsing(fn (WorkCatalog $record): string => ($record->category?->name ? $record->category->name.' · ' : '').$record->title)
                ->searchable(['title', 'code'])
                ->preload()
                ->live()
                ->afterStateUpdated(function (Set $set, ?int $state): void {
                    $service = $state ? WorkCatalog::find($state) : null;
                    $set('title', $service?->title);
                    $set('instructions', $service?->instructions);
                    $set('requirements', $service?->requirements);
                    $set('candidateEmployees', []);
                })
                ->required(),
            TextInput::make('title')->label('Tarea')->required(),
            Textarea::make('instructions')->label('Instrucciones'),
            Textarea::make('requirements')->label('Requisitos'),
            Select::make('candidateEmployees')
                ->label('Empleados candidatos')
                ->relationship(
                    name: 'candidateEmployees',
                    titleAttribute: 'name',
                    modifyQueryUsing: function (Builder $query, Get $get): Builder {
                        $categoryId = WorkCatalog::find($get('work_catalog_id'))?->work_category_id;

                        return $query
                            ->where('active', true)
                            ->when($categoryId, fn (Builder $employeeQuery): Builder => $employeeQuery->whereHas('workCategories', fn (Builder $categoryQuery): Builder => $categoryQuery->whereKey($categoryId)))
                            ->orderBy('name');
                    },
                )
                ->multiple()
                ->searchable()
                ->preload()
                ->helperText('Solo aparecen empleados activos asociados al tipo del servicio seleccionado.')
                ->columnSpanFull(),
            TextInput::make('sort')->label('Orden')->numeric()->default(0),
            Toggle::make('active')->label('Activa')->default(true),
            Repeater::make('days')->label('Días')->relationship()->schema([
                Select::make('day_of_week')->label('Día')->options([1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'])->required(),
            ])->columnSpanFull(),
        ];
    }
}
