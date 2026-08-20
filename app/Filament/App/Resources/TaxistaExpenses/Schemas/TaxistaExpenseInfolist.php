<?php

namespace App\Filament\App\Resources\TaxistaExpenses\Schemas;

use App\Models\TaxistaExpense;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class TaxistaExpenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Informacion del Gasto')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('title')
                                            ->label('Concepto')
                                            ->icon('heroicon-o-tag')
                                            ->iconColor('warning')
                                            ->weight(FontWeight::Bold),

                                        TextEntry::make('category_label')
                                            ->label('Categoria')
                                            ->getStateUsing(fn (TaxistaExpense $record): string => ($record->subcategory?->name ?: 'Sin subcategoria').' - '.($record->category?->name ?: 'Sin categoria'))
                                            ->icon('heroicon-o-tag')
                                            ->iconColor('warning')
                                            ->weight(FontWeight::Bold),

                                        TextEntry::make('taxista.name')
                                            ->label('Taxista')
                                            ->icon('heroicon-o-user')
                                            ->iconColor('info')
                                            ->weight(FontWeight::Bold),

                                        TextEntry::make('taxista.phone')
                                            ->label('Telefono')
                                            ->icon('heroicon-o-phone')
                                            ->iconColor('primary')
                                            ->weight(FontWeight::Bold)
                                            ->placeholder('-'),

                                        TextEntry::make('department.name')
                                            ->label('Departamento')
                                            ->icon('heroicon-o-building-office-2')
                                            ->iconColor('primary')
                                            ->weight(FontWeight::Bold)
                                            ->placeholder('-'),

                                        TextEntry::make('amount')
                                            ->label('Importe')
                                            ->icon('heroicon-o-currency-dollar')
                                            ->iconColor('success')
                                            ->getStateUsing(fn (TaxistaExpense $record): string => number_format((float) $record->amount, 2).' EUR')
                                            ->weight(FontWeight::Bold),

                                        TextEntry::make('total_paid')
                                            ->label('Total pagado')
                                            ->icon('heroicon-o-currency-dollar')
                                            ->iconColor('success')
                                            ->getStateUsing(fn (TaxistaExpense $record): string => number_format($record->total_paid, 2).' EUR')
                                            ->weight(FontWeight::Bold),

                                        TextEntry::make('remaining')
                                            ->label('Pendiente')
                                            ->icon('heroicon-o-currency-dollar')
                                            ->iconColor('success')
                                            ->getStateUsing(fn (TaxistaExpense $record): string => number_format($record->remaining, 2).' EUR')
                                            ->weight(FontWeight::Bold),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Informacion de pago')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('status')
                                            ->label('Estado')
                                            ->badge()
                                            ->weight(FontWeight::Bold),

                                        TextEntry::make('payment_type')
                                            ->label('Tipo pago')
                                            ->badge()
                                            ->weight(FontWeight::Bold),

                                        TextEntry::make('expense_date')
                                            ->label('Fecha gasto')
                                            ->date('m - d - Y')
                                            ->icon('heroicon-m-calendar')
                                            ->iconColor('success')
                                            ->weight(FontWeight::Bold),

                                        TextEntry::make('due_date')
                                            ->label('Vencimiento')
                                            ->date('m - d - Y')
                                            ->icon('heroicon-m-clock')
                                            ->weight(FontWeight::Bold)
                                            ->placeholder('-'),

                                        TextEntry::make('created_at')
                                            ->label('Creado')
                                            ->date('m - d - Y')
                                            ->icon('heroicon-m-calendar')
                                            ->iconColor('primary')
                                            ->weight(FontWeight::Bold),

                                        TextEntry::make('updated_at')
                                            ->label('Actualizado')
                                            ->date('m - d - Y')
                                            ->icon('heroicon-m-calendar')
                                            ->iconColor('info')
                                            ->weight(FontWeight::Bold),
                                    ]),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),

                Section::make('Informacion adicional')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Descripcion')
                            ->placeholder('-'),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }
}
