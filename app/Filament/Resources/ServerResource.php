<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\ServerResource\Pages;
use App\Filament\Resources\ServerResource\RelationManagers;
use App\Filament\Resources\ServerResource\Schemas\ServerForm;
use App\Filament\Resources\ServerResource\Tables\ServersTable;
use App\Models\Server;
use App\Services\PromptlyAgentMcpPresetCatalog;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Table;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|\UnitEnum|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?string $navigationLabel = 'MCP Servers';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 0;

    public static function form(Form $schema): Form
    {
        return ServerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServersTable::configure($table);
    }

    public static function promptlyAgentServerAction(): Action
    {
        return Action::make('installPromptlyAgentServer')
            ->label('Install PromptlyAgent')
            ->icon(Heroicon::OutlinedSparkles)
            ->schema([
                Forms\Components\Select::make('server')
                    ->label('Server preset')
                    ->options(fn (): array => app(PromptlyAgentMcpPresetCatalog::class)->serverOptions())
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data): void {
                $tools = app(PromptlyAgentMcpPresetCatalog::class)->installServer($data['server']);

                Notification::make()
                    ->title('PromptlyAgent MCP preset installed')
                    ->body("Installed {$tools->count()} tool(s).")
                    ->success()
                    ->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ToolsRelationManager::class,
            RelationManagers\ResourcesRelationManager::class,
            RelationManagers\PromptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
        ];
    }
}
