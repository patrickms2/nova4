<?php

namespace App\Filament\App\Resources\TaxistaTaxis\Pages;

use App\Filament\Portal\Pages\TaxistaTracking;
use App\Filament\App\Resources\TaxistaTaxis\Tables\TaxistaTaxisTable;
use Filament\Actions\Action;
use App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource;
use App\Models\TaxistaTaxi;
use App\Support\PortalTaxistaContext;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTaxistaTaxis extends ListRecords
{
    protected static string $resource = TaxistaTaxiResource::class;

    protected static ?string $title = 'Taxis';

    public function getSubheading(): ?string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Gestiona tus taxis y revisa su estado rapidamente.';
        }

        return null;
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()->label('Todo'),
            'assigned' => Tab::make()
                ->label('Asignados')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaTaxi::query())
                    ->whereNotNull('taxista_user_id')
                    ->count())
                ->badgeColor('info')
                ->icon('heroicon-m-user')
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->whereNotNull('taxista_user_id')),
            'unassigned' => Tab::make()
                ->label('Sin asignar')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaTaxi::query())
                    ->whereNull('taxista_user_id')
                    ->count())
                ->badgeColor('gray')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->whereNull('taxista_user_id')),
            'active' => Tab::make()
                ->label('Activos')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaTaxi::query())
                    ->where('status', 'activo')
                    ->count())
                ->badgeColor('success')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->where('status', 'activo')),
            'inactive' => Tab::make()
                ->label('Baja')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaTaxi::query())
                    ->where('status', 'baja')
                    ->count())
                ->badgeColor('danger')
                ->icon('heroicon-m-no-symbol')
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->where('status', 'baja')),
            'connected' => Tab::make()
                ->label('Conectados')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaTaxi::query())
                    ->whereNotNull('last_located_at')
                    ->where('last_located_at', '>=', now()->subMinutes(TaxistaTaxisTable::onlineThresholdMinutes()))
                    ->count())
                ->badgeColor('success')
                ->icon('heroicon-m-signal')
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->whereNotNull('last_located_at')
                    ->where('last_located_at', '>=', now()->subMinutes(TaxistaTaxisTable::onlineThresholdMinutes()))),
            'disconnected' => Tab::make()
                ->label('Desconectados')
                ->badge(fn (): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaTaxi::query())
                    ->where(function (Builder $query): void {
                        $query
                            ->whereNull('last_located_at')
                            ->orWhere('last_located_at', '<', now()->subMinutes(TaxistaTaxisTable::onlineThresholdMinutes()));
                    })
                    ->count())
                ->badgeColor('warning')
                ->icon('heroicon-m-wifi')
                ->modifyQueryUsing(fn (Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->where(function (Builder $subQuery): void {
                        $subQuery
                            ->whereNull('last_located_at')
                            ->orWhere('last_located_at', '<', now()->subMinutes(TaxistaTaxisTable::onlineThresholdMinutes()));
                    })),
        ];
    }

    protected function getHeaderActions(): array
    {
        $mapUrl = '#';

        try {
            $mapUrl = TaxistaTracking::getUrl(panel: 'portal');
        } catch (\Throwable) {
            $mapUrl = '#';
        }

        return [
            Action::make('view_map')
                ->label('Ver en mapa')
                ->icon('heroicon-o-map')
                ->url($mapUrl)
                ->openUrlInNewTab()
                ->color('gray'),
            CreateAction::make()
                ->label('Nuevo taxi'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return TaxistaTaxiResource::getWidgets();
    }
}
