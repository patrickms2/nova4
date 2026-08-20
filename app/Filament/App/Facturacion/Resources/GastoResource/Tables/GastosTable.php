<?php

namespace App\Filament\App\Facturacion\Resources\GastoResource\Tables;

use App\Models\Gasto;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class GastosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Id')->sortable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('codigo')
                    ->label('Código')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function (Gasto $record): HtmlString {
                        $icon = $record->documento
                            ? '<a href="'.asset('storage/'.$record->documento).'" target="_blank" class="text-primary hover:text-primary/80 ml-1" title="Ver ticket adjunto"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></a>'
                            : '';

                        return new HtmlString(($record->codigo ?? '—').$icon);
                    }),
                TextColumn::make('fecha')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('descripcion')->label('Descripción')->searchable()->wrap(),
                TextColumn::make('category.name')->label('Categoría')->badge()->placeholder('—'),
                TextColumn::make('proveedor.nombretotal')->label('Proveedor')->placeholder('—'),
                TextColumn::make('base_imponible')->label('Base')->money('EUR')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('impuesto')->label('Impuesto')->money('EUR')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('EUR')
                    ->color('danger')
                    ->weight('font-bold'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'pagado' => 'success',
                        'cancelado' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(Gasto::estados()),
                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('fecha')
                    ->label('Fecha')
                    ->form([
                        Section::make()
                            ->schema([
                                DatePicker::make('desde')->label('Desde')->native(false),
                                DatePicker::make('hasta')->label('Hasta')->native(false),
                            ])
                            ->columns(2),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                EditAction::make()->iconButton()->tooltip('Editar'),
                DeleteAction::make()->iconButton()->tooltip('Eliminar'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
            ]);
    }
}
