<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Models\Server;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

final class ManageNovaBusinessChat extends Page
{
    use InteractsWithRecord;

    protected static string $resource = NovaBusinessResource::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Chat';
    protected static ?string $navigationParentItem = 'MCP';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.server-chat';

    public ?int $server = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        self::authorizeResourceAccess();

        $this->server = (int) Server::query()
            ->where('nova_business_id', $this->getRecord()->id)
            ->where('is_active', true)
            ->value('id');
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Chat directo con los servidores MCP de este negocio.';
    }
}
