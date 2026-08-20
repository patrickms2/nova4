<?php

namespace App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NovaIntegrationSyncLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('integrationSetting.name')->label('Integración')->searchable()->sortable()->limit(35),
                TextColumn::make('source')->label('Origen')->badge()->sortable(),
                TextColumn::make('job_name')->label('Job')->badge()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->color(fn (?string $state): string => $state === 'ok' ? 'success' : 'danger')->sortable(),
                TextColumn::make('processed_count')->label('Procesados')->numeric()->sortable(),
                TextColumn::make('created_count')->label('Creados')->numeric()->sortable(),
                TextColumn::make('updated_count')->label('Actualizados')->numeric()->sortable(),
                TextColumn::make('error_message')->label('Error')->limit(70)->toggleable(),
                TextColumn::make('processed_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options([
                    'ok' => 'OK',
                    'failed' => 'Fallido',
                ]),
                SelectFilter::make('source')->label('Origen')->options([
                    'woo' => 'WooCommerce',
                    'magento' => 'Magento',
                    'latepoint' => 'LatePoint',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}
