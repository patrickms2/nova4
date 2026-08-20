<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\Pagos\Tables;

use Filament\Support\Icons\Heroicon;

use App\Enums\PagoEstado;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class PagoTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn (Model $record): string => route('app.filament.clusters.taxistas.pagos', ['record' => $record]),
            )
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('usuario.nombre')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->sortable(),
                Tables\Columns\TextColumn::make('importe')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado.nombre')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ref_pago')
                    ->sortable(),
                Tables\Columns\TextColumn::make('recogida')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('referencia')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('personas')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fecha_servicio')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notificado')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('metodo_pago')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fecha_terminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('fecha_alta')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                AdvancedFilter::make(),
            ])
            ->recordActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['taxista_id'] = auth()->id();
                        $data['usuario_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                ViewAction::make(),
                Action::make('add-pago')
                    ->icon(Heroicon::OutlinedPlus)
                    ->label('Pago')
                    ->schema([
                        Section::make('Detalles Pago')
                            ->schema([
                                Forms\Components\TextInput::make('ref_pago')
                                    ->maxLength(155)
                                    ->default(fn () => 'PAG-' . mb_strtoupper(mb_substr(md5(time()), 0, 8)))
                                    ->columns(1),
                                TextInput::make('importe')
                                    ->disabled(true)
                                    ->maxLength(155)
                                    ->default(null),
                                Forms\Components\TextInput::make('pagado')
                                    ->maxLength(155)
                                    ->default(null),
                                Forms\Components\ToggleButtons::make('metodo_pago')
                                    ->options([
                                        'C' => 'Contado',
                                        'T' => 'Transferencia',
                                        'R' => 'Redsys',
                                        'Z' => 'Bizum',
                                    ])
                                    ->default('C'),
                                Forms\Components\DateTimePicker::make('fecha_pago')
                                    ->label('F. Pago')
                                    ->native(false)
                                    ->nullable(),
                            ])
                            ->compact()
                            ->columns(2),
                    ])
                    ->fillForm(function ($record, $data) {
                        $data['id'] = $record->pago_id;
                        $data['importe'] = $record->importe;
                        $data['pagado'] = $record->importe;
                        $data['usuario_id'] = auth()->id();
                        $data['status'] = PagoEstado::PAGADO;
                        $data['estado_id'] = 1;
                        $data['fecha_pago'] = Carbon::now()->format('Y-m-d');
                        return $data;
                    })
                    ->mutateDataUsing(function($data, $record) {
                        $data['id'] = $record->pago_id;
                        $data['importe'] = $record->importe;
                        $data['pagado'] = $record->importe;
                        $data['usuario_id'] = $record->usuario_id;;
                        $data['status'] = PagoEstado::PAGADO;
                        $data['estado_id'] = 1;
                        $data['fecha_pago'] = Carbon::now()->format('Y-m-d');
                        return $data;
                    })
                    ->action(fn($record, $data) => Pago::create($data))
                    ->after(function($record) {
                        Notification::make()
                            ->body("Task created successfully")
                            ->success()
                            ->send();
                    }),
                Action::make('Pagar')
                    ->color('primary')
                    ->icon(
                        Heroicon::OutlinedCheck
                    )
                    ->action(function (Pago $record): void {


                    })
                    ->requiresConfirmation()
                    ->visible(
                        fn (Pago $record): bool => $record->estado_id == 3? true : false
                    ),



            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

}
