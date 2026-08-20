<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaServices\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NovaServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Servicio')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('service_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'whatsapp_bot' => 'WhatsApp Bot',
                        'development' => 'Desarrollo',
                        'maintenance' => 'Mantenimiento',
                        'sales' => 'Venta',
                        'services' => 'Servicios',
                        default => 'Otro',
                    }),

                IconColumn::make('has_development')->label('DES')->boolean(),
                IconColumn::make('has_maintenance')->label('MAN')->boolean(),
                IconColumn::make('has_whatsapp')->label('WA')->boolean(),
                IconColumn::make('has_mcp')->label('MCP')->boolean(),
                IconColumn::make('has_sales')->label('VENTA')->boolean(),
                IconColumn::make('has_services')->label('SERV')->boolean(),

                TextColumn::make('monthly_amount')
                    ->label('Cuota')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'paused' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
