<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\Concerns\CanSyncNovaBusinessIntegrations;
use App\Models\NovaIntegrationSetting;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

final class ManageNovaBusinessIntegrations extends Page implements HasTable
{
    use CanSyncNovaBusinessIntegrations;
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = NovaBusinessResource::class;

    protected string $view = 'filament.app.resources.nova-businesses.pages.manage-nova-business-table';

    protected static ?string $navigationLabel = 'Integraciones';
    protected static ?string $navigationParentItem = 'Ajustes';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?int $navigationSort = 11;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        self::authorizeResourceAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();
        $count = $record->integrationSettings()->count();

        return (string) cache()->remember(
                    static::class . '.' . $record->id . '.navigation-badge',
                    now()->addMinute(),
                    fn () => $count > 0 ? (string) $count : null
                );
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Integraciones externas configuradas para este cliente Nova.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => NovaIntegrationSetting::query()->where('nova_business_id', $this->getRecord()->id))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('Integración')->searchable()->sortable()->weight('bold'),
                TextColumn::make('source_type')->label('Origen')->badge()->sortable(),
                TextColumn::make('connection_type')->label('Conexión')->badge()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('base_url')->label('Base URL')->limit(45)->searchable()->toggleable(),
                TextColumn::make('last_sync_started_at')->label('Inicio sync')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
                TextColumn::make('last_sync_finished_at')->label('Fin sync')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('last_sync_failed_at')->label('Error sync')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
                TextColumn::make('last_sync_error')->label('Último error')->limit(60)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('source_type')->label('Origen')->options([
                    'woo' => 'WooCommerce',
                    'latepoint' => 'LatePoint',
                    'woo_latepoint' => 'Woo + LatePoint',
                    'magento' => 'Magento',
                    'wordpress' => 'WordPress',
                    'laravel' => 'Laravel',
                ]),
                SelectFilter::make('status')->label('Estado')->options([
                    'active' => 'Activa',
                    'paused' => 'Pausada',
                    'draft' => 'Borrador',
                ]),
            ])
            ->recordActions([
                Action::make('openIntegration')
                    ->label('Editar')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (NovaIntegrationSetting $record): string => url('/admin/nova-integration-settings/'.$record->getKey().'/edit'))
                    ->openUrlInNewTab(false),
                Action::make('logs')
                    ->label('Logs')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->url(fn (NovaIntegrationSetting $record): string => url('/admin/nova-businesses/'.$this->getRecord()->getKey().'/logs-sync?integration='.$record->id)),
            ])
            ->defaultSort('id', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->syncIntegrationsAction(),
        ];
    }
}
