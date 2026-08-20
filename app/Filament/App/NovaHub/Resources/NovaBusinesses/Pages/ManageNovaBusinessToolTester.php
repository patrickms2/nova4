<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Models\Tool;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

final class ManageNovaBusinessToolTester extends Page
{
    use InteractsWithRecord;

    protected static string $resource = NovaBusinessResource::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static ?string $navigationLabel = 'Tool Tester';
    protected static \UnitEnum|string|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentItem = 'MCP';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.tool-tester';

    public ?int $tool = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        self::authorizeResourceAccess();

        $this->tool = (int) Tool::query()
            ->whereHas('server', fn ($q) => $q->where('nova_business_id', $this->getRecord()->id))
            ->where('is_active', true)
            ->value('id');
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Prueba tools MCP de los servidores de este negocio.';
    }
}
