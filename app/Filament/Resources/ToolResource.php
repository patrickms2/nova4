<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\ToolResource\Pages;
use App\Filament\Resources\ToolResource\Schemas\ToolForm;
use App\Filament\Resources\ToolResource\Tables\ToolsTable;
use App\Models\Server;
use App\Models\Tool;
use App\Services\PromptlyAgentMcpPresetCatalog;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Table;

class ToolResource extends Resource
{
    protected static ?string $model = Tool::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentGroup = 'IA';
    protected static ?string $navigationLabel = 'Tools';

    protected static ?string $modelLabel = 'Tool';

    protected static ?string $pluralModelLabel = 'Tools';
    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 2;

    public static function form(Form $schema): Form
    {
        return ToolForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ToolsTable::configure($table);
    }

    public static function promptlyAgentToolAction(): Action
    {
        return Action::make('installPromptlyAgentTool')
            ->label('Install PromptlyAgent tool')
            ->icon(Heroicon::OutlinedSparkles)
            ->schema([
                Forms\Components\Select::make('server_id')
                    ->label('Target server')
                    ->options(fn (): array => Server::query()->pluck('name', 'id')->all())
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Select::make('tool')
                    ->label('Tool preset')
                    ->options(fn (): array => app(PromptlyAgentMcpPresetCatalog::class)->toolOptions())
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data): void {
                $server = Server::query()->findOrFail($data['server_id']);
                $tool = app(PromptlyAgentMcpPresetCatalog::class)->installTool($server, $data['tool']);

                Notification::make()
                    ->title('PromptlyAgent tool installed')
                    ->body("Installed {$tool->title}.")
                    ->success()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTools::route('/'),
            'create' => Pages\CreateTool::route('/create'),
            'edit' => Pages\EditTool::route('/{record}/edit'),
        ];
    }
}
