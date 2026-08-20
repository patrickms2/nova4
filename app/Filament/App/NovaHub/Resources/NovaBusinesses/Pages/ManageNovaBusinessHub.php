<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Models\NovaIntegrationSetting;
use App\Models\Server;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class ManageNovaBusinessHub extends ViewRecord
{
    protected static string $resource = NovaBusinessResource::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'Hub';
    protected static ?string $navigationParentItem = 'Ajustes';

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.pages.nova-business-hub';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->url(fn () => NovaBusinessResource::getUrl('edit', ['record' => $this->getRecord()]))
                ->icon(Heroicon::OutlinedPencilSquare),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cliente / negocio')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')
                        ->label('Nombre')
                        ->weight('bold'),
                    TextEntry::make('business_type')
                        ->label('Tipo')
                        ->badge(),
                    TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'active' => 'success',
                            'paused' => 'warning',
                            'inactive' => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('contact_name')->label('Contacto')->placeholder('—'),
                    TextEntry::make('contact_email')->label('Email')->placeholder('—'),
                    TextEntry::make('contact_phone')->label('Teléfono')->placeholder('—'),
                    TextEntry::make('website_url')
                        ->label('Web')
                        ->url(fn ($state) => $state)
                        ->openUrlInNewTab()
                        ->placeholder('—'),
                    TextEntry::make('subscription_amount')
                        ->label('Suscripción')
                        ->money('EUR')
                        ->placeholder('—'),
                    TextEntry::make('commission_rate')
                        ->label('Comisión')
                        ->suffix('%')
                        ->placeholder('—'),
                ]),
        ]);
    }

    public function getViewData(): array
    {
        $business = $this->getRecord()->load([
            'services',
            'mcpServers',
            'aiProfiles',
            'whatsappChannels',
            'listingCategories',
            'intentRules',
            'crossSellingRules',
        ]);

        $mcpServers = Server::query()
            ->where('nova_business_id', $business->id)
            ->withCount(['tools', 'prompts'])
            ->orderBy('name')
            ->get();

        $integrations = NovaIntegrationSetting::query()
            ->where('nova_business_id', $business->id)
            ->get();

        $stats = [
            'services' => $business->services->count(),
            'mcp_servers' => $mcpServers->count(),
            'tools' => $mcpServers->sum('tools_count'),
            'whatsapp' => $business->whatsappChannels->count(),
            'ai_profiles' => $business->aiProfiles->count(),
            'listing' => $business->listingCategories->count(),
            'integrations' => $integrations->count(),
        ];

        $mermaid = $this->buildMermaidDiagram($business, $mcpServers);

        return [
            'business' => $business,
            'services' => $business->services,
            'mcpServers' => $mcpServers,
            'aiProfiles' => $business->aiProfiles,
            'whatsappChannels' => $business->whatsappChannels,
            'listingCategories' => $business->listingCategories,
            'integrations' => $integrations,
            'stats' => $stats,
            'mermaid' => $mermaid,
        ];
    }

    private function buildMermaidDiagram(mixed $business, mixed $mcpServers): string
    {
        $bizLabel = $this->mermaidLabel($business->name);
        $lines = [];
        $lines[] = 'graph LR';
        $lines[] = "    BIZ([\"🏢 {$bizLabel}\"])";
        $lines[] = '    style BIZ fill:#3b82f6,stroke:#1d4ed8,color:#fff,font-weight:bold';

        foreach ($business->services as $service) {
            $sid = 'SVC'.$service->id;
            $slabel = $this->mermaidLabel($service->name);
            $lines[] = "    {$sid}[\"{$slabel}\"]";
            $lines[] = "    style {$sid} fill:#f0fdf4,stroke:#16a34a,color:#166534";
            $lines[] = "    BIZ --> {$sid}";
        }

        foreach ($mcpServers as $server) {
            $mid = 'MCP'.$server->id;
            $mlabel = $this->mermaidLabel($server->name);
            $lines[] = "    {$mid}[\"⚙ {$mlabel}\n{$server->tools_count} tools\"]";
            $lines[] = "    style {$mid} fill:#eff6ff,stroke:#3b82f6,color:#1e40af";
            $lines[] = "    BIZ --> {$mid}";
        }

        foreach ($business->aiProfiles as $profile) {
            $aid = 'AI'.$profile->id;
            $alabel = $this->mermaidLabel($profile->name);
            $lines[] = "    {$aid}[\"🤖 {$alabel}\"]";
            $lines[] = "    style {$aid} fill:#f5f3ff,stroke:#7c3aed,color:#4c1d95";
            $lines[] = "    BIZ --> {$aid}";
        }

        foreach ($business->whatsappChannels as $wa) {
            $wid = 'WA'.$wa->id;
            $wlabel = $this->mermaidLabel($wa->name);
            $lines[] = "    {$wid}[\"💬 {$wlabel}\"]";
            $lines[] = "    style {$wid} fill:#fefce8,stroke:#ca8a04,color:#713f12";
            $lines[] = "    BIZ --> {$wid}";
        }

        if ($business->services->isEmpty() && $mcpServers->isEmpty() && $business->aiProfiles->isEmpty()) {
            $lines[] = '    EMPTY["Sin recursos configurados"]';
            $lines[] = '    style EMPTY fill:#f9fafb,stroke:#d1d5db,color:#9ca3af';
            $lines[] = '    BIZ --> EMPTY';
        }

        return implode("\n", $lines);
    }

    private function mermaidLabel(string $text): string
    {
        return str_replace(['"', "'", '(', ')', "\n", "\r"], [' ', ' ', '-', '-', ' ', ''], $text);
    }
}
