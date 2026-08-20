<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Servicios\RelationManagers;

use App\Filament\App\NovaHub\Resources\Servicios\Taxis\TaxiResource;

use App\Models\Taxi\Taxi;
use App\Models\Taxi\TipoTaxis;
use App\Models\Taxi\Turnos;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

use Filament\Schemas\Schema as Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables;
use Filament\Tables\Table;

class TaxisRelationManager extends RelationManager
{
    protected static string $relationship = 'taxis';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('recordId')
                    ->label('Taxi')
                    ->options(Taxi::pluck('matricula', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),


            ]);
    }

    public function form2(Form $form): Form
    {
        return (new TaxiResource())->form($form);
    }

    public function table(Table $table): Table
    {
        return (new TaxiResource())
            ->table($table)
            ->headerActions([ // Agregar acciones en el encabezado de la tabla
                CreateAction::make(), // Usar slideOver para mejor UX
                AttachAction::make()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->searchable()
                            ->preload(),
                        Select::make('recordId')
                            ->label('Taxi')
                            ->options(function () {
                                // Exclude the current category if editing
                                $query = Taxi::query();
                                return $query->pluck('matricula', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->preload()
                            ->columnSpan('xs'),

                    ])
                    ->preloadRecordSelect(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([
                10, 25, 50
            ]);
    }
}
