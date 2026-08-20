<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\RelationManagers;

use App\Filament\App\Rentals\Resources\RentalReservationResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->components([
                Select::make('source')
                    ->options([
                        'airbnb' => 'Airbnb',
                        'booking' => 'Booking',
                        'direct' => 'Directo',
                        'stripe' => 'Stripe',
                        'transfer' => 'Transferencia',
                    ])
                    ->required(),
                TextInput::make('amount')->numeric()->prefix('€')->required(),
                DatePicker::make('expected_at')->label('Fecha prevista'),
                DatePicker::make('paid_at')->label('Fecha de pago'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'paid' => 'Pagado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('pending')
                    ->required(),
                TextInput::make('transaction_id')->label('ID transacción'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                TextColumn::make('source')->label('Origen'),
                TextColumn::make('amount')->label('Importe')->money('EUR'),
                TextColumn::make('expected_at')->label('Previsto')->date('d M Y'),
                TextColumn::make('paid_at')->label('Pagado')->date('d M Y'),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('transaction_id')->label('Transacción'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['rental_reservation_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ]);
    }
}
