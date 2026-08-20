<?php

namespace App\Filament\App\Resources\TaxistaExpenses\Schemas;

use App\Enums\TaxistaExpensePaymentStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaxistaExpensePaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('taxista_expense_id'),

                Hidden::make('paid_by_user_id')
                    ->default(fn (): ?int => auth()->id()),

                TextInput::make('amount')
                    ->label('Importe pago')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('EUR'),

                Select::make('status')
                    ->label('Estado')
                    ->options(TaxistaExpensePaymentStatus::class)
                    ->default(TaxistaExpensePaymentStatus::Paid)
                    ->native(false)
                    ->required(),

                DatePicker::make('payment_date')
                    ->label('Fecha pago')
                    ->default(now())
                    ->required(),

                TextInput::make('reference')
                    ->label('Referencia')
                    ->maxLength(100),

                Textarea::make('notes')
                    ->label('Notas')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
