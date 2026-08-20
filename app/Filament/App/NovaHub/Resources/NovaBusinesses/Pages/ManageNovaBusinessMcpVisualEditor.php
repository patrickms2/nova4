<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Models\Server;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ManageNovaBusinessMcpVisualEditor extends ViewRecord
{
    protected static string $resource = NovaBusinessResource::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static ?string $navigationLabel = 'Editor Visual MCP';
    protected static ?string $navigationParentItem = 'MCP';


    protected static string|\UnitEnum|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentGroup = 'Clientes';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.nova-business-mcp-visual-editor';

    public function getViewData(): array
    {
        $business = $this->getRecord();

        $servers = Server::query()
            ->where('nova_business_id', $business->id)
            ->with(['tools', 'resources', 'prompts'])
            ->orderBy('name')
            ->get();

        return [
            'business' => $business,
            'servers' => $servers,
        ];
    }
}
