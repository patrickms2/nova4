<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Panel;
use App\Models\PanelField;
use App\Models\PanelRelation;
use App\Models\PanelTable;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IntegratedPanelManager extends Component
{
    use WithPagination;

    // Panel Management
    public $panels = [];
    public $currentPanel = null;
    public $showPanelForm = false;
    public $panelId = null;
    public $name = '';
    public $slug = '';
    public $description = '';
    public $icon = 'heroicon-o-cube';
    public $navigationGroup = '';
    public $navigationSort = 0;
    public $isActive = true;

    // Field Management
    public $showFieldForm = false;
    public $fieldId = null;
    public $fieldName = '';
    public $fieldLabel = '';
    public $fieldType = 'string';
    public $filamentFieldType = 'TextInput';
    public $columnType = 'TextColumn';
    public $fieldNullable = false;
    public $fieldDefault = '';

    // Relation Management
    public $showRelationForm = false;
    public $relationId = null;
    public $relationType = 'belongsTo';
    public $relatedModel = '';
    public $foreignKey = '';
    public $relationMethodName = '';

    // Table Management
    public $showTableForm = false;
    public $tableId = null;
    public $tableName = '';
    public $tableTitle = '';
    public $tableDescription = '';
    public $isDefaultTable = false;

    // Navigation
    public $currentView = 'panels'; // panels, resources, models
    public $selectedPanel = null;

    // Available options
    public $availableFieldTypes = [
        'string' => 'String',
        'text' => 'Text',
        'integer' => 'Integer',
        'bigint' => 'Big Integer',
        'decimal' => 'Decimal',
        'boolean' => 'Boolean',
        'date' => 'Date',
        'datetime' => 'DateTime',
        'timestamp' => 'Timestamp',
        'json' => 'JSON',
    ];

    public $availableFilamentFieldTypes = [
        'TextInput' => 'Text Input',
        'Textarea' => 'Textarea',
        'RichEditor' => 'Rich Editor',
        'Select' => 'Select',
        'Checkbox' => 'Checkbox',
        'Toggle' => 'Toggle',
        'DatePicker' => 'Date Picker',
        'DateTimePicker' => 'DateTime Picker',
        'FileUpload' => 'File Upload',
        'MarkdownEditor' => 'Markdown Editor',
        'ColorPicker' => 'Color Picker',
        'Repeater' => 'Repeater',
    ];

    public $availableColumnTypes = [
        'TextColumn' => 'Text Column',
        'TextareaColumn' => 'Textarea Column',
        'SelectColumn' => 'Select Column',
        'CheckboxColumn' => 'Checkbox Column',
        'ToggleColumn' => 'Toggle Column',
        'DateColumn' => 'Date Column',
        'DateTimeColumn' => 'DateTime Column',
        'ImageColumn' => 'Image Column',
        'FileColumn' => 'File Column',
        'ColorColumn' => 'Color Column',
        'IconColumn' => 'Icon Column',
        'BadgeColumn' => 'Badge Column',
    ];

    public $availableRelationTypes = [
        'belongsTo' => 'Belongs To',
        'hasMany' => 'Has Many',
        'hasOne' => 'Has One',
        'belongsToMany' => 'Belongs To Many',
        'morphMany' => 'Morph Many',
        'morphOne' => 'Morph One',
    ];

    protected $listeners = [
        'panel-selected' => 'selectPanel',
        'edit-panel' => 'editPanel',
        'delete-panel' => 'deletePanel',
    ];

    public function mount()
    {
        $this->loadPanels();
    }

    public function loadPanels()
    {
        $this->panels = Panel::orderBy('navigation_group')->orderBy('navigation_sort')->get();
    }

    public function selectPanel($panelId)
    {
        $this->selectedPanel = Panel::with(['fields', 'relations', 'tables'])->find($panelId);
        $this->currentView = 'panel-detail';
    }

    public function switchView($view)
    {
        $this->currentView = $view;
        if ($view === 'panels') {
            $this->selectedPanel = null;
        } elseif ($view === 'visual' && $this->selectedPanel) {
            // Redirect to React Flow editor with selected panel
            return redirect()->route('react-flow-editor', ['panelId' => $this->selectedPanel->id]);
        } elseif ($view === 'visual' && !$this->selectedPanel) {
            // If no panel selected, show message or redirect to panel selection
            session()->flash('message', 'Please select a panel first to open the visual editor');
            $this->currentView = 'panels';
        }
    }

    // Panel Management Methods
    public function createPanel()
    {
        $this->resetPanelForm();
        $this->showPanelForm = true;
    }

    public function editPanel($panelId)
    {
        $panel = Panel::find($panelId);
        $this->panelId = $panelId;
        $this->name = $panel->name;
        $this->slug = $panel->slug;
        $this->description = $panel->description;
        $this->icon = $panel->icon;
        $this->navigationGroup = $panel->navigation_group;
        $this->navigationSort = $panel->navigation_sort;
        $this->isActive = $panel->is_active;
        $this->showPanelForm = true;
    }

    public function savePanel()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:panels,slug,' . $this->panelId,
        ]);

        // Prepare default schema
        $defaultSchema = [
            'model_name' => Str::studly($this->name),
            'table_name' => strtolower(str_replace(' ', '_', $this->name)),
            'fields' => [],
            'relations' => [],
        ];

        $panel = Panel::updateOrCreate(
            ['id' => $this->panelId],
            [
                'name' => $this->name,
                'slug' => Str::slug($this->name),
                'description' => $this->description,
                'icon' => $this->icon,
                'navigation_group' => $this->navigationGroup,
                'navigation_sort' => $this->navigationSort,
                'model_schema' => $defaultSchema,
                'resource_config' => [],
                'is_active' => $this->isActive,
                'created_by' => auth()->id() ?? 1,
            ]
        );

        $this->showPanelForm = false;
        $this->resetPanelForm();
        $this->loadPanels();

        session()->flash('message', $this->panelId ? 'Panel updated successfully!' : 'Panel created successfully!');
    }

    public function deletePanel($panelId)
    {
        Panel::find($panelId)->delete();
        $this->loadPanels();
        session()->flash('message', 'Panel deleted successfully!');
    }

    private function resetPanelForm()
    {
        $this->panelId = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->icon = 'heroicon-o-cube';
        $this->navigationGroup = '';
        $this->navigationSort = 0;
        $this->isActive = true;
    }

    // Field Management Methods
    public function createField()
    {
        $this->resetFieldForm();
        $this->showFieldForm = true;
    }

    public function saveField()
    {
        $this->validate([
            'fieldName' => 'required|string|max:255',
            'fieldLabel' => 'required|string|max:255',
        ]);

        PanelField::updateOrCreate(
            ['id' => $this->fieldId],
            [
                'panel_id' => $this->selectedPanel->id,
                'name' => $this->fieldName,
                'label' => $this->fieldLabel,
                'type' => $this->fieldType,
                'filament_type' => $this->filamentFieldType,
                'column_type' => $this->columnType,
                'nullable' => $this->fieldNullable,
                'default' => $this->fieldDefault,
            ]
        );

        // Update panel's model_schema
        $this->updatePanelSchema();

        $this->showFieldForm = false;
        $this->resetFieldForm();
        $this->selectPanel($this->selectedPanel->id);
    }

    private function resetFieldForm()
    {
        $this->fieldId = null;
        $this->fieldName = '';
        $this->fieldLabel = '';
        $this->fieldType = 'string';
        $this->filamentFieldType = 'TextInput';
        $this->columnType = 'TextColumn';
        $this->fieldNullable = false;
        $this->fieldDefault = '';
    }

    // Relation Management Methods
    public function createRelation()
    {
        $this->resetRelationForm();
        $this->showRelationForm = true;
    }

    public function saveRelation()
    {
        $this->validate([
            'relationType' => 'required|string',
            'relatedModel' => 'required|string',
        ]);

        PanelRelation::updateOrCreate(
            ['id' => $this->relationId],
            [
                'panel_id' => $this->selectedPanel->id,
                'type' => $this->relationType,
                'related_model' => $this->relatedModel,
                'foreign_key' => $this->foreignKey,
                'method_name' => $this->relationMethodName ?: strtolower(Str::plural($this->relatedModel)),
            ]
        );

        // Update panel's model_schema
        $this->updatePanelSchema();

        $this->showRelationForm = false;
        $this->resetRelationForm();
        $this->selectPanel($this->selectedPanel->id);
    }

    private function resetRelationForm()
    {
        $this->relationId = null;
        $this->relationType = 'belongsTo';
        $this->relatedModel = '';
        $this->foreignKey = '';
        $this->relationMethodName = '';
    }

    // Table Management Methods
    public function createTable()
    {
        $this->resetTableForm();
        $this->showTableForm = true;
    }

    public function saveTable()
    {
        $this->validate([
            'tableName' => 'required|string|max:255',
            'tableTitle' => 'required|string|max:255',
        ]);

        PanelTable::updateOrCreate(
            ['id' => $this->tableId],
            [
                'panel_id' => $this->selectedPanel->id,
                'name' => $this->tableName,
                'title' => $this->tableTitle,
                'description' => $this->tableDescription,
                'is_default' => $this->isDefaultTable,
            ]
        );

        // Update panel's model_schema
        $this->updatePanelSchema();

        $this->showTableForm = false;
        $this->resetTableForm();
        $this->selectPanel($this->selectedPanel->id);
    }

    private function updatePanelSchema()
    {
        if (!$this->selectedPanel) return;

        // Get current schema
        $schema = $this->selectedPanel->model_schema ?? [];

        // Update fields in schema
        $fields = $this->selectedPanel->fields->map(function ($field) {
            return [
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'filament_type' => $field->filament_type,
                'column_type' => $field->column_type,
                'nullable' => $field->nullable,
                'default' => $field->default,
            ];
        })->toArray();

        // Update relations in schema
        $relations = $this->selectedPanel->relations->map(function ($relation) {
            return [
                'type' => $relation->type,
                'related_model' => $relation->related_model,
                'foreign_key' => $relation->foreign_key,
                'method_name' => $relation->method_name,
            ];
        })->toArray();

        // Update schema
        $schema['fields'] = $fields;
        $schema['relations'] = $relations;

        // Save updated schema
        $this->selectedPanel->update([
            'model_schema' => $schema
        ]);
    }

    private function resetTableForm()
    {
        $this->tableId = null;
        $this->tableName = '';
        $this->tableTitle = '';
        $this->tableDescription = '';
        $this->isDefaultTable = false;
    }

    // Resource Generation
    public function generateFilamentResource($panelId)
    {
        $panel = Panel::find($panelId);

        // Generate Filament Resource
        $resourceName = Str::studly($panel->name) . 'Resource';
        $modelClass = Str::studly($panel->name);

        // Create resource directory
        $resourceDir = app_path("Filament/Resources/{$resourceName}");
        if (!is_dir($resourceDir)) {
            mkdir($resourceDir, 0755, true);
        }

        // Generate main resource file
        $resourceContent = $this->generateResourceContent($panel, $resourceName, $modelClass);
        $resourcePath = app_path("Filament/Resources/{$resourceName}.php");
        file_put_contents($resourcePath, $resourceContent);

        // Generate pages
        $this->generateResourcePages($panel, $resourceName, $modelClass);

        session()->flash('message', "Filament Resource {$resourceName} generated successfully!");
    }

    private function generateResourceContent($panel, $resourceName, $modelClass)
    {
        $schema = $panel->model_schema ?? [];
        $fields = $schema['fields'] ?? [];

        // Generate form schema
        $formSchema = '';
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldLabel = $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldName));
            $fieldType = $field['filament_type'] ?? 'TextInput';
            $required = !($field['nullable'] ?? false);

            $formSchema .= "                \\Filament\\Forms\\Components\\{$fieldType}::make('{$fieldName}')\n";
            $formSchema .= "                    ->label('{$fieldLabel}')\n";
            if ($required) $formSchema .= "                    ->required()\n";
            $formSchema .= "                    ->maxLength(255),\n";
        }

        // Generate table columns
        $tableColumns = '';
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldLabel = $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldName));
            $columnType = $field['column_type'] ?? 'TextColumn';

            $tableColumns .= "                \\Filament\\Tables\\Columns\\{$columnType}::make('{$fieldName}')\n";
            $tableColumns .= "                    ->label('{$fieldLabel}')\n";
            $tableColumns .= "                    ->searchable(),\n";
        }

        if (empty($formSchema)) {
            $formSchema = "                // Add your form fields here\n";
        }

        if (empty($tableColumns)) {
            $tableColumns = "                // Add your table columns here\n";
        }

        return "<?php

namespace App\\Filament\\Resources;

use App\\Filament\\Resources\\{$resourceName}\\Pages;
use App\\Models\\{$modelClass};
use Filament\\Forms;
use Filament\\Schemas\\Schema as Form;
use Filament\\Resources\\Resource;
use Filament\\Tables;
use Filament\\Tables\\Table;
use App\\Model\\PanelField;
use Illuminate\\Database\\Eloquent\\Builder;
use BackedEnum;
use UnitEnum;
use Filament\\Actions;
use Filament\\Forms\\Components\\DatePicker;
use Filament\\Forms\\Components\\Repeater;
use Filament\\Forms\\Components\Repeater\\TableColumn;
use Filament\\Forms\\Components\\Select;
use Filament\\Forms\\Components\\TextInput;
use Filament\\Schemas\\Components\\Component;
use Filament\\Schemas\\Components\\Section;
use Filament\\Schemas\\Components\\Utilities\\Get;
use Filament\\Schemas\\Components\\Utilities\\Set;

class {$resourceName} extends Resource
{
    protected static ?string \$model = {$modelClass}::class;

    protected static string|\BackedEnum|null \$navigationIcon = '{$panel->icon}';

    protected static string|\UnitEnum|null \$navigationGroup = " . (!empty($panel->navigation_group) ? "'{$panel->navigation_group}'" : 'null') . ";

    protected static ?int \$navigationSort = " . (!empty($panel->navigation_sort) ? $panel->navigation_sort : 'null') . ";

    public static function form(Form \$form): Form
    {
        return \$form
            ->schema([
{$formSchema}
            ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
{$tableColumns}
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\\Actions\\EditAction::make(),
                Tables\\Actions\\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\\Actions\\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\\List{$modelClass}::route('/'),
            'create' => Pages\\Create{$modelClass}::route('/create'),
            'edit' => Pages\\Edit{$modelClass}::route('/{record}/edit'),
        ];
    }
}";
    }

    private function generateResourcePages($panel, $resourceName, $modelClass)
    {
        $resourceDir = app_path("Filament/Resources/{$resourceName}");

        // List page
        $listPageContent = "<?php

namespace App\\Filament\\Resources\\{$resourceName}\\Pages;

use App\\Filament\\Resources\\{$resourceName};
use Filament\\Actions;
use Filament\\Resources\\Pages\\ListRecords;

class List{$modelClass} extends ListRecords
{
    protected static string \$resource = {$resourceName}::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\\CreateAction::make(),
        ];
    }
}";

        file_put_contents("{$resourceDir}/List{$modelClass}.php", $listPageContent);

        // Create page
        $createPageContent = "<?php

namespace App\\Filament\\Resources\\{$resourceName}\\Pages;

use App\\Filament\\Resources\\{$resourceName};
use Filament\\Actions;
use Filament\\Resources\\Pages\\CreateRecord;

class Create{$modelClass} extends CreateRecord
{
    protected static string \$resource = {$resourceName}::class;
}";

        file_put_contents("{$resourceDir}/Create{$modelClass}.php", $createPageContent);

        // Edit page
        $editPageContent = "<?php

namespace App\\Filament\\Resources\\{$resourceName}\\Pages;

use App\\Filament\\Resources\\{$resourceName};
use Filament\\Actions;
use Filament\\Resources\\Pages\\EditRecord;

class Edit{$modelClass} extends EditRecord
{
    protected static string \$resource = {$resourceName}::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\\DeleteAction::make(),
            Actions\\RestoreAction::make(),
            Actions\\ForceDeleteAction::make(),
        ];
    }
}";

        file_put_contents("{$resourceDir}/Edit{$modelClass}.php", $editPageContent);
    }

    public function render()
    {
        return view('livewire.integrated-panel-manager', [
            'panels' => $this->panels,
            'selectedPanel' => $this->selectedPanel,
        ])->layout('layouts.app');
    }
}
