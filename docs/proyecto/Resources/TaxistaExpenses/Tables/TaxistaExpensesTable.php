<?php

namespace App\Filament\App\Resources\TaxistaExpenses\Tables;

use App\Filament\App\Resources\TaxistaExpenses\TaxistaExpenseResource;
use App\Models\TaxistaExpense;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaxistaExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['taxista', 'category', 'subcategory', 'department']))
            ->columns([
                Stack::make([
                    Stack::make([
                        TextColumn::make('taxista.name')
                            ->label('Taxista')
                            ->icon('heroicon-m-user')
                            ->iconColor('info')
                            ->weight(FontWeight::Bold)
                            ->searchable(),

                        TextColumn::make('title')
                            ->label('Concepto')
                            ->icon('heroicon-m-tag')
                            ->iconColor('warning')
                            ->weight(FontWeight::SemiBold)
                            ->extraAttributes(['class' => 'border-t dark:border-t-gray-200/20 pt-1']),

                        TextColumn::make('category_summary')
                            ->label('Categoria')
                            ->formatStateUsing(fn (TaxistaExpense $record): string => ($record->subcategory?->name ?: 'Sin subcategoria').' - '.($record->category?->name ?: 'Sin categoria'))
                            ->color('gray'),
                    ])->space(1),

                    Stack::make([
                        TextColumn::make('amount')
                            ->label('Importe')
                            ->icon('heroicon-m-currency-dollar')
                            ->iconColor('success')
                            ->formatStateUsing(fn (TaxistaExpense $record): string => 'Importe: '.number_format((float) $record->amount, 2).' EUR'),

                        TextColumn::make('target_amount')
                            ->label('Importe final')
                            ->icon('heroicon-m-currency-dollar')
                            ->iconColor('success')
                            ->formatStateUsing(fn (TaxistaExpense $record): string => 'Importe final: '.number_format($record->target_amount, 2).' EUR'),
                    ]),

                    Stack::make([
                        TextColumn::make('paid_amount')
                            ->label('Pagado')
                            ->icon('heroicon-m-currency-dollar')
                            ->iconColor('success')
                            ->formatStateUsing(fn (TaxistaExpense $record): string => 'Pagado: '.number_format($record->total_paid, 2).' EUR'),

                        TextColumn::make('remaining')
                            ->label('Pendiente')
                            ->icon('heroicon-m-currency-dollar')
                            ->iconColor('success')
                            ->formatStateUsing(fn (TaxistaExpense $record): string => 'Pendiente: '.number_format($record->remaining, 2).' EUR'),
                    ]),

                    Stack::make([
                        TextColumn::make('status')
                            ->label('Estado')
                            ->badge()
                            ->sortable(),

                        TextColumn::make('payment_type')
                            ->label('Tipo pago')
                            ->badge(),
                    ])->extraAttributes(['class' => 'flex flex-row gap-2 border-b dark:border-b-gray-200/20 pb-2']),

                    Stack::make([
                        TextColumn::make('expense_date')
                            ->label('Fecha gasto')
                            ->date('m - d - Y')
                            ->icon('heroicon-m-calendar')
                            ->iconColor('warning'),

                        TextColumn::make('due_date')
                            ->label('Vencimiento')
                            ->date('m - d - Y')
                            ->icon('heroicon-m-clock')
                            ->iconColor('info')
                            ->placeholder('-'),

                        TextColumn::make('updated_at')
                            ->label('Actualizado')
                            ->date('m - d - Y')
                            ->icon('heroicon-m-pencil-square')
                            ->iconColor('info'),
                    ]),
                ])
                    ->extraAttributes(['class' => 'flex gap-3']),
            ])
            ->groups([
                Group::make('taxista.name')
                    ->label('Taxista'),
                Group::make('payment_type')
                    ->label('Tipo pago'),
                Group::make('status')
                    ->label('Estado'),
                Group::make('expense_date')
                    ->label('Fecha gasto')
                    ->date(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'partial' => 'Parcial',
                        'completed' => 'Completado',
                        'canceled' => 'Cancelado',
                    ]),
                SelectFilter::make('payment_type')
                    ->label('Tipo pago')
                    ->options([
                        'onetime' => 'Unico',
                        'recurring' => 'Recurrente',
                    ]),
            ])
            ->defaultSort('expense_date', 'desc')
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                '2xl' => 3,
            ])
            ->recordActions([
                Action::make('togglePriority')
                    ->hiddenLabel()
                    ->icon(fn (TaxistaExpense $record): string => $record->is_priority ? 'heroicon-s-shield-exclamation' : 'heroicon-o-shield-exclamation')
                    ->color(fn (TaxistaExpense $record): string => $record->is_priority ? 'warning' : 'gray')
                    ->size('xl')
                    ->action(fn (TaxistaExpense $record) => $record->update(['is_priority' => ! $record->is_priority])),

                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('payments')
                        ->label('Pagos')
                        ->icon('heroicon-o-currency-dollar')
                        ->url(fn (TaxistaExpense $record): string => TaxistaExpenseResource::getUrl('payments', ['record' => $record])),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
