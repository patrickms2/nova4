<?php

namespace App\Filament\Pages;

use Filament\Support\Icons\Heroicon;

use App\Models\Panel;
use App\Models\PanelField;
use App\Models\PanelRelation;
use App\Models\PanelTable;
use Filament\Forms;
use Filament\Schemas\Schema as Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class PanelManagement extends Page
{
    use InteractsWithForms;

    protected  string $view = 'filament.pages.panel-management';
 protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?int $navigationSort = 0;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Create New Panel')
                    ->description('Create a new panel for your application')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(fn (string $operation, string $state, Set $set) => $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Panel::class, 'slug',),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('navigation_group')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('icon')
                            ->maxLength(255)
                            ->default(Heroicon::OutlinedCube),
                        Forms\Components\TextInput::make('navigation_sort')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $panel = Panel::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
            'navigation_group' => $data['navigation_group'] ?? '',
            'navigation_sort' => $data['navigation_sort'] ?? 0,
            'icon' => $data['icon'] ?? Heroicon::OutlinedCube,
            'is_active' => $data['is_active'] ?? true,
            'model_schema' => [
                'model_name' => str_replace(' ', '', $data['name']),
                'table_name' => strtolower(str_replace(' ', '_', $data['name'])),
                'fields' => [],
                'relations' => [],
            ],
        ]);

        Notification::make()
            ->title('Panel created successfully')
            ->body("Panel '{$panel->name}' has been created.")
            ->success()
            ->send();

        $this->form->fill();
    }

    public function getStats(): array
    {
        return [
            'panels' => Panel::count(),
            'fields' => PanelField::count(),
            'relations' => PanelRelation::count(),
            'tables' => PanelTable::count(),
        ];
    }

    public function getRecentPanels(): \Illuminate\Database\Eloquent\Collection
    {
        return Panel::withCount(['fields', 'relations', 'tables'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
            'recentPanels' => $this->getRecentPanels(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_panel')
                ->label('Create Panel')
                ->icon(Heroicon::OutlinedPlus)
                ->action(fn () => $this->create()),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Panel')
                ->action('create')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
