<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\Tables\NovaIntegrationSyncLogsTable;
use App\Models\NovaIntegrationSyncLog;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Livewire;

final class ManageNovaBusinessSyncLogs extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = NovaBusinessResource::class;

    protected string $view = 'filament.app.resources.nova-businesses.pages.manage-nova-business-table';

    protected static ?string $navigationLabel = 'Logs de sync';
    protected static ?string $navigationParentItem = 'Ajustes';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 17;

    #[Url(as: 'integration')]
    public ?int $integrationId = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        self::authorizeResourceAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();
        $count = NovaIntegrationSyncLog::query()->where('nova_business_id', $record->id)->count();

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
        return $this->integrationId
            ? 'Logs de sincronización filtrados por integración.'
            : 'Logs de sincronización de todas las integraciones de este cliente.';
    }

    public function table(Table $table): Table
    {
        return NovaIntegrationSyncLogsTable::configure($table)
            ->query(fn (): Builder => NovaIntegrationSyncLog::query()
                ->where('nova_business_id', $this->getRecord()->id)
                ->when($this->integrationId, fn (Builder $query): Builder => $query->where('nova_integration_setting_id', $this->integrationId)));
    }
}
