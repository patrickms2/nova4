<?php

namespace App\Filament\Pages;

use Filament\Support\Icons\Heroicon;

use App\Models\Panel;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Route;
use BackedEnum;
use UnitEnum;

class PanelBuilderDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected  string $view = 'filament.pages.panel-builder-dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?int $navigationSort = 0;

    public $panels = [];
    public $selectedPanel = null;
    public $showCreateForm = false;
    public $showFieldBuilder = false;
    public $showRelationBuilder = false;
    public $showTableBuilder = false;

    public function mount(): void
    {
        $this->loadPanels();
    }

    public function loadPanels(): void
    {
        $this->panels = Panel::with(['fields', 'relations', 'tables'])
            ->orderBy('navigation_group')
            ->orderBy('navigation_sort')
            ->get();
    }

    public function selectPanel($panelId): void
    {
        $this->selectedPanel = Panel::with(['fields', 'relations', 'tables'])->find($panelId);
    }

    public function createPanel(): void
    {
        $this->showCreateForm = true;
    }

    public function openFieldBuilder($panelId = null): void
    {
        if ($panelId) {
            $this->selectPanel($panelId);
        }
        $this->showFieldBuilder = true;
    }

    public function openRelationBuilder($panelId = null): void
    {
        if ($panelId) {
            $this->selectPanel($panelId);
        }
        $this->showRelationBuilder = true;
    }

    public function openTableBuilder($panelId = null): void
    {
        if ($panelId) {
            $this->selectPanel($panelId);
        }
        $this->showTableBuilder = true;
    }

    public function generatePanelCode($panelId): void
    {
        $panel = Panel::find($panelId);
        if (!$panel) return;

        // Generate code files
        $this->generateModel($panel);
        $this->generateMigration($panel);
        $this->generateResource($panel);

        $this->dispatch('code-generated', panelId: $panelId);
    }

    private function generateModel(Panel $panel): void
    {
        $code = $panel->generateModelCode();
        $className = $panel->model_schema['model_name'] ?? str_replace(' ', '', $panel->name);

        $modelPath = app_path("Models/{$className}.php");
        file_put_contents($modelPath, $code);
    }

    private function generateMigration(Panel $panel): void
    {
        $code = $panel->generateMigrationCode();
        $tableName = $panel->model_schema['table_name'] ?? strtolower(str_replace(' ', '_', $panel->name));
        $timestamp = now()->format('Y_m_d_His');

        $migrationPath = database_path("migrations/{$timestamp}_create_{$tableName}_table.php");
        file_put_contents($migrationPath, $code);
    }

    private function generateResource(Panel $panel): void
    {
        $code = $panel->generateResourceCode();
        $className = $panel->model_schema['model_name'] ?? str_replace(' ', '', $panel->name);
        $resourceName = $className . 'Resource';

        $resourceDir = app_path("Filament/Resources");
        if (!is_dir($resourceDir)) {
            mkdir($resourceDir, 0755, true);
        }

        $resourcePath = app_path("Filament/Resources/{$resourceName}.php");
        file_put_contents($resourcePath, $code);
    }

    #[On('panel-created')]
    public function onPanelCreated(): void
    {
        $this->loadPanels();
        $this->showCreateForm = false;
    }

    #[On('field-created')]
    public function onFieldCreated(): void
    {
        $this->loadPanels();
        if ($this->selectedPanel) {
            $this->selectPanel($this->selectedPanel->id);
        }
    }

    #[On('relation-created')]
    public function onRelationCreated(): void
    {
        $this->loadPanels();
        if ($this->selectedPanel) {
            $this->selectPanel($this->selectedPanel->id);
        }
    }

    #[On('table-created')]
    public function onTableCreated(): void
    {
        $this->loadPanels();
        if ($this->selectedPanel) {
            $this->selectPanel($this->selectedPanel->id);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_panel')
                ->label('Create Panel')
                ->icon(Heroicon::OutlinedPlus)
                ->action(fn () => $this->createPanel()),
        ];
    }

    public function getStats(): array
    {
        return [
            'total_panels' => Panel::count(),
            'active_panels' => Panel::where('is_active', true)->count(),
            'total_fields' => \App\Models\PanelField::count(),
            'total_relations' => \App\Models\PanelRelation::count(),
            'total_tables' => \App\Models\PanelTable::count(),
        ];
    }
}
