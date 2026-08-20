<?php

namespace App\Filament\App\Facturacion\Resources\ConceptoResource\Tables;

use App\Models\Taxi\Departamento;
use App\Models\Taxi\TipoDoc;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ConceptosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codconcepto')->label('Código')->sortable()->searchable(),
                TextColumn::make('concepto')->label('Concepto')->wrap()->searchable(),
                TextColumn::make('cliente.nombretotal')->label('Cliente')->badge(),
                TextColumn::make('categoria')->badge(),
                TextColumn::make('precio')->numeric(2),
                TextColumn::make('impuesto')->label('IGIC %'),
                TextColumn::make('retenciones')->label('Ret. %'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                    BulkAction::make('Cambiar Departamento')
                        ->icon(Heroicon::PencilSquare)
                        ->schema([
                            Select::make('departamento_id')
                                ->label('Departamentos')
                                ->options(fn () => Departamento::query()->where('estado', 1)->pluck('nombre', 'id'))
                                ->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['departamento_id' => $data['departamento_id']]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('activate')
                        ->label('Activar')
                        ->icon(Heroicon::CheckCircle)
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn ($r) => $r->update(['estado' => 1]));
                            Notification::make()->title('Documentos activados')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('desactivate')
                        ->label('Desactivar')
                        ->icon(Heroicon::XCircle)
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn ($r) => $r->update(['estado' => 0]));
                            Notification::make()->title('Documentos desactivados')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('Cambiar Tipo')
                        ->icon(Heroicon::PencilSquare)
                        ->schema([
                            Select::make('tipo_id')
                                ->label('Tipos')
                                ->options(fn () => TipoDoc::query()->where('estado', 1)->pluck('nombre', 'id'))
                                ->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['tipo_id' => $data['tipo_id']]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('Cambiar Usuario')
                        ->icon(Heroicon::PencilSquare)
                        ->schema([
                            Select::make('usuario_id')
                                ->label('Usuarios')
                                ->default(1)
                                ->relationship('usuario', 'nombre')
                                ->searchable()
                                ->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['usuario_id' => $data['usuario_id']]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
