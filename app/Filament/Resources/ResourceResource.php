<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\ResourceResource\Pages;
use App\Models\Resource as ResourceModel;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;

class ResourceResource extends Resource
{
    protected static ?string $model = ResourceModel::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'IA';
    protected static ?string $navigationParentGroup = 'MCP';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Resource';

    protected static ?string $pluralModelLabel = 'Resources';

    public static function form(Form $schema): Form
    {
        return $schema->schema([
            Section::make('Resource Details')
                ->schema([
                    Forms\Components\Select::make('server_id')
                        ->relationship('server', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('name')
                        ->placeholder('api-docs')
                        ->helperText('Lowercase identifier with hyphens'),
                    Forms\Components\TextInput::make('title')
                        ->placeholder('API Documentation'),
                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->placeholder('Comprehensive documentation for the API endpoints'),
                ])
                ->columns(2),
            Section::make('URI Configuration')
                ->schema([
                    Forms\Components\TextInput::make('uri')
                        ->placeholder('myapp://docs/api')
                        ->helperText('Static URI for this resource (e.g., myapp://docs/readme)'),
                    Forms\Components\TextInput::make('uri_template')
                        ->placeholder('myapp://users/{userId}/profile')
                        ->helperText('Optional: URI template with placeholders for dynamic resources'),
                    Forms\Components\Select::make('mime_type')
                        ->options([
                            'text/plain' => 'Plain Text',
                            'text/markdown' => 'Markdown',
                            'text/html' => 'HTML',
                            'application/json' => 'JSON',
                            'application/xml' => 'XML',
                            'application/pdf' => 'PDF',
                            'image/png' => 'PNG Image',
                            'image/jpeg' => 'JPEG Image',
                        ])
                        ->default('text/plain')
                        ->required(),
                ])
                ->columns(3),

            Section::make('Content')
                ->schema([
                    Tabs::make('Content Type')
                        ->tabs([
                            Tab::make('Static Content')
                                ->schema([
                                    Forms\Components\Textarea::make('content')
                                        ->rows(15)
                                        ->placeholder('Enter the static content for this resource...
# Example Markdown Content
This content will be returned directly when the resource is accessed.
## Features
- Simple static content
- No code execution required
- Great for documentation')
                                        ->helperText('Static content returned when this resource is accessed. Leave empty if using dynamic handler.'),
                                ]),
                            Tab::make('Dynamic Handler')
                                ->schema([
                                    Forms\Components\Textarea::make('handler_code')
                                        ->placeholder('// Access URI template parameters via $input array
// Example for URI template: myapp://users/{userId}/profile
$userId = $input["userId"] ?? null;
if (!$userId) {
    return "User ID is required";
}
// Fetch user data (example)
$user = \App\Models\User::find($userId);
if (!$user) {
    return "User not found";
return json_encode([
    "id" => $user->id,
    "name" => $user->name,
    "email" => $user->email,
], JSON_PRETTY_PRINT);')
                                        ->helperText('PHP code that generates dynamic content. Use $input to access URI template parameters.'),
                                ]),
                        ]),
                ]),
            Section::make('Annotations')
                ->schema([
                    Grid::make(3)->schema([

                        Forms\Components\Select::make('annotations.audience')
                            ->label('Audience')
                            ->options([
                                'user' => 'User',
                                'assistant' => 'Assistant',
                            ])
                            ->placeholder('Select audience')
                            ->helperText('Who this resource is intended for'),
                        Forms\Components\TextInput::make('annotations.priority')
                            ->label('Priority')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.1)
                            ->placeholder('0.5')
                            ->helperText('Priority from 0 to 1'),
                        Forms\Components\DateTimePicker::make('annotations.lastModified')
                            ->label('Last Modified')
                            ->helperText('When the content was last updated'),
                    ]),
                ])
                ->collapsed(),
            Section::make('Status')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive resources are not exposed via MCP'),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Order in which resources appear'),
                ]),

        ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('title')
                    ->limit(30),
                Tables\Columns\TextColumn::make('uri')
                    ->copyable()
                    ->copyMessage('URI copied')
                    ->limit(40),
                Tables\Columns\TextColumn::make('mime_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text/markdown' => 'info',
                        'application/json' => 'warning',
                        'text/html' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_dynamic')
                    ->label('Dynamic')
                    ->boolean()
                    ->getStateUsing(fn (ResourceModel $record): bool => ! empty($record->handler_code)),
                Tables\Columns\IconColumn::make('is_template')
                    ->label('Template')
                    ->getStateUsing(fn (ResourceModel $record): bool => ! empty($record->uri_template)),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('server')
                    ->relationship('server', 'name')->searchable(),
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\SelectFilter::make('mime_type')
                    ->options([
                        'text/plain' => 'Plain Text',
                        'text/markdown' => 'Markdown',
                        'text/html' => 'HTML',
                        'application/json' => 'JSON',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('preview')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (ResourceModel $record) => "Preview: {$record->title}")
                    ->modalContent(fn (ResourceModel $record) => view('filament.resources.resource-preview', ['resource' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResources::route('/'),
            'create' => Pages\CreateResource::route('/create'),
            'edit' => Pages\EditResource::route('/{record}/edit'),
        ];
    }
}
