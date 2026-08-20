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

class PanelBuilder extends Component
{
    use WithPagination;

    public $panels = [];
    public $currentPanel = null;
    public $showPanelForm = false;
    public $showFieldForm = false;
    public $showRelationForm = false;
    public $showTableForm = false;

    // Panel form properties
    public $panelId = null;
    public $name = '';
    public $slug = '';
    public $description = '';
    public $icon = 'heroicon-o-cube';
    public $navigationGroup = '';
    public $navigationSort = 0;
    public $isActive = true;

    // Field form properties
    public $fieldId = null;
    public $fieldName = '';
    public $fieldLabel = '';
    public $fieldType = 'string';
    public $filamentFieldType = 'TextInput';
    public $columnType = 'TextColumn';
    public $fieldNullable = false;
    public $fieldDefault = '';
    public $fieldValidation = [];
    public $fieldOrder = 0;
    public $fieldConfig = [];

    // Relation form properties
    public $relationId = null;
    public $relationType = 'belongsTo';
    public $relatedModel = '';
    public $relatedPanelId = null;
    public $foreignKey = '';
    public $localKey = '';
    public $relationMethodName = '';
    public $relationConfig = [];

    // Table form properties
    public $tableId = null;
    public $tableName = '';
    public $tableTitle = '';
    public $tableDescription = '';
    public $tableColumns = [];
    public $tableFilters = [];
    public $tableActions = [];
    public $tableBulkActions = [];
    public $tableConfig = [];
    public $isDefaultTable = false;

    // Available field types
    public $availableFieldTypes = [
        'string' => 'String',
        'text' => 'Text',
        'integer' => 'Integer',
        'bigInteger' => 'Big Integer',
        'decimal' => 'Decimal',
        'boolean' => 'Boolean',
        'date' => 'Date',
        'datetime' => 'DateTime',
        'timestamp' => 'Timestamp',
        'json' => 'JSON',
        'foreignId' => 'Foreign ID',
    ];

    public $availableFilamentFieldTypes = [
        'TextInput' => 'Text Input',
        'Textarea' => 'Textarea',
        'RichEditor' => 'Rich Editor',
        'Select' => 'Select',
        'Checkbox' => 'Checkbox',
        'Toggle' => 'Toggle',
        'Radio' => 'Radio',
        'DatePicker' => 'Date Picker',
        'DateTimePicker' => 'Date Time Picker',
        'TimePicker' => 'Time Picker',
        'FileUpload' => 'File Upload',
        'ColorPicker' => 'Color Picker',
        'Hidden' => 'Hidden',
    ];

    public $availableColumnTypes = [
        'TextColumn' => 'Text Column',
        'IconColumn' => 'Icon Column',
        'ImageColumn' => 'Image Column',
        'BooleanColumn' => 'Boolean Column',
        'BadgeColumn' => 'Badge Column',
        'ColorColumn' => 'Color Column',
        'NumberColumn' => 'Number Column',
        'DateColumn' => 'Date Column',
        'DateTimeColumn' => 'Date Time Column',
        'TimeColumn' => 'Time Column',
    ];

    public $availableRelationTypes = [
        'belongsTo' => 'Belongs To',
        'hasMany' => 'Has Many',
        'hasOne' => 'Has One',
        'belongsToMany' => 'Belongs To Many',
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:panels,slug',
        'fieldName' => 'required|string|max:255',
        'fieldLabel' => 'required|string|max:255',
        'fieldType' => 'required|string',
        'filamentFieldType' => 'required|string',
        'columnType' => 'required|string',
        'relationType' => 'required|string',
        'relatedModel' => 'required|string',
        'tableName' => 'required|string|max:255',
        'tableTitle' => 'required|string|max:255',
    ];

    public function mount()
    {
        $this->loadPanels();
    }

    public function loadPanels()
    {
        $this->panels = Panel::with(['fields', 'relations', 'tables'])
            ->orderBy('navigation_group')
            ->orderBy('navigation_sort')
            ->get();
    }

    public function createPanel()
    {
        $this->resetPanelForm();
        $this->showPanelForm = true;
    }

    public function editPanel($panelId)
    {
        $panel = Panel::find($panelId);
        if (!$panel) return;

        $this->panelId = $panel->id;
        $this->name = $panel->name;
        $this->slug = $panel->slug;
        $this->description = $panel->description;
        $this->icon = $panel->icon;
        $this->navigationGroup = $panel->navigation_group;
        $this->navigationSort = $panel->navigation_sort;
        $this->isActive = $panel->is_active;

        $this->currentPanel = $panel;
        $this->showPanelForm = true;
    }

    public function savePanel()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('panels', 'slug')->ignore($this->panelId),
            ],
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'navigation_group' => $this->navigationGroup,
            'navigation_sort' => $this->navigationSort,
            'is_active' => $this->isActive,
            'model_schema' => [
                'model_name' => str_replace(' ', '', $this->name),
                'table_name' => strtolower(str_replace(' ', '_', $this->name)),
                'fields' => [],
                'relations' => [],
            ],
        ];

        if ($this->panelId) {
            Panel::find($this->panelId)->update($data);
            session()->flash('message', 'Panel updated successfully.');
        } else {
            $panel = Panel::create($data);
            $this->panelId = $panel->id;
            session()->flash('message', 'Panel created successfully.');
        }

        $this->loadPanels();
        $this->showPanelForm = false;
        $this->resetPanelForm();
    }

    public function deletePanel($panelId)
    {
        Panel::find($panelId)->delete();
        $this->loadPanels();
        session()->flash('message', 'Panel deleted successfully.');
    }

    public function resetPanelForm()
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

    // Field methods
    public function createField($panelId)
    {
        $this->currentPanel = Panel::find($panelId);
        $this->resetFieldForm();
        $this->showFieldForm = true;
    }

    public function editField($fieldId)
    {
        $field = PanelField::find($fieldId);
        if (!$field) return;

        $this->fieldId = $field->id;
        $this->fieldName = $field->name;
        $this->fieldLabel = $field->label;
        $this->fieldType = $field->type;
        $this->filamentFieldType = $field->filament_type;
        $this->columnType = $field->column_type;
        $this->fieldNullable = $field->nullable;
        $this->fieldDefault = $field->default;
        $this->fieldValidation = $field->validation_rules ?? [];
        $this->fieldOrder = $field->order;
        $this->fieldConfig = $field->field_config ?? [];

        $this->showFieldForm = true;
    }

    public function saveField()
    {
        $this->validate([
            'fieldName' => 'required|string|max:255',
            'fieldLabel' => 'required|string|max:255',
            'fieldType' => 'required|string',
            'filamentFieldType' => 'required|string',
            'columnType' => 'required|string',
        ]);

        $data = [
            'panel_id' => $this->currentPanel->id,
            'name' => $this->fieldName,
            'label' => $this->fieldLabel,
            'type' => $this->fieldType,
            'filament_type' => $this->filamentFieldType,
            'column_type' => $this->columnType,
            'nullable' => $this->fieldNullable,
            'default' => $this->fieldDefault,
            'validation_rules' => $this->fieldValidation,
            'order' => $this->fieldOrder,
            'field_config' => $this->fieldConfig,
        ];

        if ($this->fieldId) {
            PanelField::find($this->fieldId)->update($data);
            session()->flash('message', 'Field updated successfully.');
        } else {
            PanelField::create($data);
            session()->flash('message', 'Field created successfully.');
        }

        $this->updatePanelSchema();
        $this->loadPanels();
        $this->showFieldForm = false;
        $this->resetFieldForm();
    }

    public function deleteField($fieldId)
    {
        PanelField::find($fieldId)->delete();
        $this->updatePanelSchema();
        $this->loadPanels();
        session()->flash('message', 'Field deleted successfully.');
    }

    public function resetFieldForm()
    {
        $this->fieldId = null;
        $this->fieldName = '';
        $this->fieldLabel = '';
        $this->fieldType = 'string';
        $this->filamentFieldType = 'TextInput';
        $this->columnType = 'TextColumn';
        $this->fieldNullable = false;
        $this->fieldDefault = '';
        $this->fieldValidation = [];
        $this->fieldOrder = 0;
        $this->fieldConfig = [];
    }

    // Relation methods
    public function createRelation($panelId)
    {
        $this->currentPanel = Panel::find($panelId);
        $this->resetRelationForm();
        $this->showRelationForm = true;
    }

    public function saveRelation()
    {
        $this->validate([
            'relationType' => 'required|string',
            'relatedModel' => 'required|string',
        ]);

        $data = [
            'panel_id' => $this->currentPanel->id,
            'type' => $this->relationType,
            'related_model' => $this->relatedModel,
            'related_panel_id' => $this->relatedPanelId,
            'foreign_key' => $this->foreignKey,
            'local_key' => $this->localKey,
            'method_name' => $this->relationMethodName,
            'relation_config' => $this->relationConfig,
        ];

        if ($this->relationId) {
            PanelRelation::find($this->relationId)->update($data);
            session()->flash('message', 'Relation updated successfully.');
        } else {
            PanelRelation::create($data);
            session()->flash('message', 'Relation created successfully.');
        }

        $this->updatePanelSchema();
        $this->loadPanels();
        $this->showRelationForm = false;
        $this->resetRelationForm();
    }

    public function deleteRelation($relationId)
    {
        PanelRelation::find($relationId)->delete();
        $this->updatePanelSchema();
        $this->loadPanels();
        session()->flash('message', 'Relation deleted successfully.');
    }

    public function resetRelationForm()
    {
        $this->relationId = null;
        $this->relationType = 'belongsTo';
        $this->relatedModel = '';
        $this->relatedPanelId = null;
        $this->foreignKey = '';
        $this->localKey = '';
        $this->relationMethodName = '';
        $this->relationConfig = [];
    }

    // Table methods
    public function createTable($panelId)
    {
        $this->currentPanel = Panel::find($panelId);
        $this->resetTableForm();
        $this->showTableForm = true;
    }

    public function saveTable()
    {
        $this->validate([
            'tableName' => 'required|string|max:255',
            'tableTitle' => 'required|string|max:255',
        ]);

        $data = [
            'panel_id' => $this->currentPanel->id,
            'name' => $this->tableName,
            'title' => $this->tableTitle,
            'description' => $this->tableDescription,
            'columns' => $this->tableColumns,
            'filters' => $this->tableFilters,
            'actions' => $this->tableActions,
            'bulk_actions' => $this->tableBulkActions,
            'table_config' => $this->tableConfig,
            'is_default' => $this->isDefaultTable,
        ];

        if ($this->tableId) {
            PanelTable::find($this->tableId)->update($data);
            session()->flash('message', 'Table updated successfully.');
        } else {
            PanelTable::create($data);
            session()->flash('message', 'Table created successfully.');
        }

        $this->loadPanels();
        $this->showTableForm = false;
        $this->resetTableForm();
    }

    public function deleteTable($tableId)
    {
        PanelTable::find($tableId)->delete();
        $this->loadPanels();
        session()->flash('message', 'Table deleted successfully.');
    }

    public function resetTableForm()
    {
        $this->tableId = null;
        $this->tableName = '';
        $this->tableTitle = '';
        $this->tableDescription = '';
        $this->tableColumns = [];
        $this->tableFilters = [];
        $this->tableActions = [];
        $this->tableBulkActions = [];
        $this->tableConfig = [];
        $this->isDefaultTable = false;
    }

    // Helper methods
    public function updatePanelSchema()
    {
        if (!$this->currentPanel) return;

        $schema = $this->currentPanel->model_schema;

        // Update fields in schema
        $schema['fields'] = $this->currentPanel->fields->map(function ($field) {
            return [
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'filament_type' => $field->filament_type,
                'column_type' => $field->column_type,
                'nullable' => $field->nullable,
                'default' => $field->default,
                'validation_rules' => $field->validation_rules,
                'field_config' => $field->field_config,
            ];
        })->toArray();

        // Update relations in schema
        $schema['relations'] = $this->currentPanel->relations->map(function ($relation) {
            return [
                'type' => $relation->type,
                'related_model' => $relation->related_model,
                'foreign_key' => $relation->foreign_key,
                'local_key' => $relation->local_key,
                'method_name' => $relation->method_name,
                'relation_config' => $relation->relation_config,
            ];
        })->toArray();

        $this->currentPanel->update(['model_schema' => $schema]);
    }

    public function updatedName()
    {
        $this->slug = Str::slug($this->name);
    }

    public function generateCode($panelId)
    {
        $panel = Panel::find($panelId);
        if (!$panel) return;

        // Generate and save files
        $this->generateModelFile($panel);
        $this->generateMigrationFile($panel);
        $this->generateResourceFile($panel);

        session()->flash('message', 'Code generated successfully!');
    }

    private function generateModelFile($panel)
    {
        $code = $panel->generateModelCode();
        $className = $panel->model_schema['model_name'] ?? str_replace(' ', '', $panel->name);

        file_put_contents(app_path("Models/{$className}.php"), $code);
    }

    private function generateMigrationFile($panel)
    {
        $code = $panel->generateMigrationCode();
        $tableName = $panel->model_schema['table_name'] ?? strtolower(str_replace(' ', '_', $panel->name));
        $timestamp = now()->format('Y_m_d_His');

        file_put_contents(database_path("migrations/{$timestamp}_create_{$tableName}_table.php"), $code);
    }

    private function generateResourceFile($panel)
    {
        $code = $panel->generateResourceCode();
        $className = $panel->model_schema['model_name'] ?? str_replace(' ', '', $panel->name);
        $resourceName = $className . 'Resource';

        if (!is_dir(app_path("Filament/Resources"))) {
            mkdir(app_path("Filament/Resources"), 0755, true);
        }

        file_put_contents(app_path("Filament/Resources/{$resourceName}.php"), $code);
    }

    public function render()
    {
        return view('livewire.panel-builder')
            ->layout('layouts.app');
    }
}
