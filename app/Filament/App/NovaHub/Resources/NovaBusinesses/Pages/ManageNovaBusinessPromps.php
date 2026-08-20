<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Models\Prompt;
use App\Models\Server;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;

use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema as Form;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

final class ManageNovaBusinessPromps extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = NovaBusinessResource::class;

    protected static ?string $navigationLabel = 'Promps';
    protected static ?string $navigationParentItem = 'IA';
    protected static \UnitEnum|string|null $navigationGroup = 'Nova Hub';

    protected static \UnitEnum|string|null $navigationParentGroup = 'Clientes';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.promps';

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
        return 'Información que el chat IA puede usar para responder sobre este cliente.';
    }

    protected function getHeaderActions(): array
    {
        return [

            CreateAction::make()
                ->label('Nuevo Promp')
                ->icon(Heroicon::OutlinedPlus)
                ->color('danger')
                ->mutateDataUsing(function (array $data): array {
                    $data['server_id'] = $this->server;

                    return $data;
                }),
        ];
    }

    public function form(Form $schema): Form
    {
        return $schema->schema([
            Section::make('Prompt Details')
                ->schema([
                    Select::make('server_id')
                        ->label('Servidor MCP')
                        ->options(fn (): array => Server::query()
                            ->where('nova_business_id', $this->getRecord()->id)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray())
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->placeholder('code-review')
                        ->helperText('Lowercase identifier with hyphens'),
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->placeholder('Code Review Assistant'),
                    Forms\Components\Textarea::make('description')
                        ->required()
                        ->rows(3)
                        ->placeholder('A prompt that helps review code for best practices and potential issues'),
                ])
                ->columns(2),

            Section::make('Arguments')
                ->schema([
                    Forms\Components\Repeater::make('arguments')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->placeholder('language')
                                ->helperText('Argument name'),
                            Forms\Components\TextInput::make('description')
                                ->placeholder('The programming language of the code')
                                ->helperText('Description shown to AI client'),
                            Forms\Components\Toggle::make('required')
                                ->default(false)
                                ->inline(false),
                        ])
                        ->columns(3)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Argument')
                        ->addActionLabel('Add Argument')
                        ->defaultItems(0)
                        ->helperText('Define arguments that can be passed when using this prompt. Use {argumentName} in messages to reference them.'),
                ]),

            Section::make('Messages')
                ->schema([
                    Repeater::make('messages')
                        ->schema([
                            Select::make('role')
                                ->options([
                                    'system' => 'System',
                                    'user' => 'User',
                                    'assistant' => 'Assistant',
                                ])
                                ->default('user')
                                ->required(),
                            Textarea::make('content')
                                ->required()
                                ->rows(5)
                                ->placeholder('You are a helpful {language} code reviewer. Please analyze the following code for:
- Best practices
- Potential bugs
- Performance improvements
- Security issues')
                                ->helperText('Use {argumentName} to insert argument values'),
                        ])
                        ->columns(1)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => ucfirst($state['role'] ?? 'user').' message')
                        ->addActionLabel('Add Message')
                        ->defaultItems(1)
                        ->reorderable()
                        ->helperText('Define the conversation messages. Arguments like {language} will be replaced with actual values.'),
                ]),

            Section::make('Metadata')
                ->schema([
                    KeyValue::make('metadata')
                        ->keyLabel('Key')
                        ->valueLabel('Value')
                        ->helperText('Additional metadata to attach to this prompt'),
                ])
                ->collapsed(),

            Section::make('Settings')
                ->schema([
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive prompts are not exposed via MCP'),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Order in which prompts appear'),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {

        return $table
            ->query(fn (): Builder => Prompt::query())
            ->modifyQueryUsing(fn (Builder $query) => $query->whereHas(
                'server',
                fn (Builder $q) => $q->where('nova_business_id', $this->getRecord()->id)
            ))
            ->columns([
                TextColumn::make('server.name')
                    ->label('Server')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('arguments_count')
                    ->label('Args')
                    ->getStateUsing(fn (Prompt $record): int => count($record->arguments ?? []))
                    ->badge()
                    ->color('info'),
                TextColumn::make('messages_count')
                    ->label('Messages')
                    ->getStateUsing(fn (Prompt $record): int => count($record->messages ?? []))
                    ->badge()
                    ->color('success'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('server')
                    ->relationship('server', 'name')->searchable(),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('preview')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (Prompt $record) => "Preview: {$record->title}")
                    ->modalContent(fn (Prompt $record) => view('filament.resources.prompt-preview-modal', ['prompt' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('duplicate')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->action(function (Prompt $record) {
                        $newPrompt = $record->replicate();
                        $newPrompt->name = $record->name.'-copy';
                        $newPrompt->title = $record->title.' (Copy)';
                        $newPrompt->save();
                    })
                    ->successNotificationTitle('Prompt duplicated'),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                    Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon(Heroicon::OutlinedCheck)
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon(Heroicon::OutlinedXMark)
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}
