<?php

namespace App\Filament\App\NovaHub\Resources\NovaIntegrationSettings\Tables;

use Filament\Support\Icons\Heroicon;

use App\Models\NovaIntegrationSetting;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class NovaIntegrationSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Integración')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('business.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source_type')
                    ->label('Origen')
                    ->badge()
                    ->sortable(),
                TextColumn::make('connection_type')
                    ->label('Conexión')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'paused' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('last_sync_finished_at')
                    ->label('Última sync')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('last_sync_error')
                    ->label('Último error')
                    ->limit(60)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('source_type')
                    ->label('Origen')
                    ->options([
                        'woo' => 'WooCommerce',
                        'latepoint' => 'LatePoint',
                        'woo_latepoint' => 'Woo + LatePoint',
                        'magento' => 'Magento',
                    ]),
                SelectFilter::make('connection_type')
                    ->label('Conexión')
                    ->options([
                        'api' => 'API',
                        'database' => 'Base de datos',
                        'mcp' => 'MCP',
                    ]),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activa',
                        'paused' => 'Pausada',
                        'draft' => 'Borrador',
                    ]),
            ])
            ->headerActions([
                Action::make('registerFromEnv')
                    ->label('Registrar desde .env')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->action(function (): void {
                        Artisan::call('nova:register-external-integrations', ['--no-interaction' => true]);

                        Notification::make()
                            ->title('Integraciones registradas')
                            ->body(Str::limit(Artisan::output(), 500))
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('sync')
                    ->label('Sync')
                    ->icon(Heroicon::OutlinedBolt)
                    ->action(fn (NovaIntegrationSetting $record): mixed => self::runSync($record, false)),
                Action::make('fullSync')
                    ->label('Full sync')
                    ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->requiresConfirmation()
                    ->action(fn (NovaIntegrationSetting $record): mixed => self::runSync($record, true)),
                EditAction::make()
                    ->modalHeading('Editar integración')
                    ->modalDescription('Edita los detalles de la integración')
                    ->modalWidth('2xl')
                    ->action(fn (NovaIntegrationSetting $record): mixed => self::EditModal($record, true)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

     private static function EditModal(NovaIntegrationSetting $record, bool $full): void
    {
        dd($record, $full);
        $command = $record->source_type === 'magento'
            ? 'nova:sync-magento'
            : 'nova:sync-woo-latepoint';

        $parameters = [
            '--integration' => $record->getKey(),
            '--no-interaction' => true,
        ];

        if ($full) {
            $parameters['--full'] = true;
        }

        $exitCode = Artisan::call($command, $parameters);
        $output = Artisan::output();

        Notification::make()
            ->title($exitCode === 0 ? 'Sincronización completada' : 'Sincronización fallida')
            ->body(Str::limit($output, 800))
            ->status($exitCode === 0 ? 'success' : 'danger')
            ->send();
    }
    private static function runSync(NovaIntegrationSetting $record, bool $full): void
    {
        $command = $record->source_type === 'magento'
            ? 'nova:sync-magento'
            : 'nova:sync-woo-latepoint';

        $parameters = [
            '--integration' => $record->getKey(),
            '--no-interaction' => true,
        ];

        if ($full) {
            $parameters['--full'] = true;
        }

        $exitCode = Artisan::call($command, $parameters);
        $output = Artisan::output();

        Notification::make()
            ->title($exitCode === 0 ? 'Sincronización completada' : 'Sincronización fallida')
            ->body(Str::limit($output, 800))
            ->status($exitCode === 0 ? 'success' : 'danger')
            ->send();
    }
}
