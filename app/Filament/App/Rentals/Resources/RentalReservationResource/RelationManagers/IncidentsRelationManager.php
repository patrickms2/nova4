<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\RelationManagers;

use App\Filament\App\Rentals\Resources\RentalReservationResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IncidentsRelationManager extends RelationManager
{
    protected static string $relationship = 'incidents';

    public function form(Form $form): Form
    {
        return $form
            ->components([
                TextInput::make('title')->label('Título')->required(),
                Textarea::make('description')->label('Descripción')->rows(3),
                Select::make('status')
                    ->options([
                        'open' => 'Abierta',
                        'in_progress' => 'En progreso',
                        'resolved' => 'Resuelta',
                        'closed' => 'Cerrada',
                    ])
                    ->default('open')
                    ->required(),
                Select::make('priority')
                    ->options([
                        'low' => 'Baja',
                        'medium' => 'Media',
                        'high' => 'Alta',
                    ])
                    ->default('medium'),
                TextInput::make('assignee_name')->label('Responsable'),
                TextInput::make('estimated_cost')->numeric()->prefix('€')->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->label('Título')->searchable(),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('priority')->label('Prioridad')->badge(),
                TextColumn::make('estimated_cost')->label('Coste estimado')->money('EUR'),
                TextColumn::make('created_at')->label('Fecha')->date('d M Y'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['rental_property_id'] = $this->getOwnerRecord()->rental_property_id;
                        $data['rental_reservation_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ]);
    }
}
