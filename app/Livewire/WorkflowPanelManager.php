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

class WorkflowPanelManager extends Component
{
    use WithPagination;

    // Workflow state management
    public $currentWorkflow = null;
    public $workflowStep = 1;
    public $workflowData = [];
    public $workflowHistory = [];
    public $isWorkflowActive = false;

    // Panel Management
    public $panels = [];
    public $currentPanel = null;
    public $selectedPanel = null;

    // Workflow steps configuration
    public $workflowSteps = [
        1 => ['name' => 'panel_setup', 'title' => 'Panel Configuration', 'description' => 'Configure basic panel settings'],
        2 => ['name' => 'model_design', 'title' => 'Model Design', 'description' => 'Define model fields and structure'],
        3 => ['name' => 'relations_setup', 'title' => 'Relations Setup', 'description' => 'Configure model relationships'],
        4 => ['name' => 'resource_config', 'title' => 'Resource Configuration', 'description' => 'Configure Filament resource settings'],
        5 => ['name' => 'code_generation', 'title' => 'Code Generation', 'description' => 'Generate and review code'],
    ];

    // Available options (similar to IntegratedPanelManager)
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

    protected $listeners = [
        'start-workflow' => 'startWorkflow',
        'workflow-step-completed' => 'completeWorkflowStep',
        'workflow-cancel' => 'cancelWorkflow',
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
        // You can add additional logic here if needed, like switching to a detail view
    }

    // Workflow Management
    public function startWorkflow($type = 'create', $panelId = null)
    {
        $this->isWorkflowActive = true;
        $this->workflowStep = 1;
        $this->workflowData = [];
        $this->workflowHistory = [];

        if ($type === 'edit' && $panelId) {
            $this->selectedPanel = Panel::with(['fields', 'relations', 'tables'])->find($panelId);
            $this->initializeWorkflowFromPanel();
        } else {
            $this->initializeNewWorkflow();
        }

        $this->currentWorkflow = $type;
    }

    private function initializeNewWorkflow()
    {
        $this->workflowData = [
            'panel_setup' => [
                'name' => '',
                'slug' => '',
                'description' => '',
                'icon' => 'heroicon-o-cube',
                'navigation_group' => '',
                'navigation_sort' => 0,
                'is_active' => true,
            ],
            'model_design' => [
                'model_name' => '',
                'table_name' => '',
                'fields' => [],
            ],
            'relations_setup' => [
                'relations' => [],
            ],
            'resource_config' => [
                'resource_settings' => [],
                'table_columns' => [],
                'form_fields' => [],
            ],
            'code_generation' => [
                'generated_files' => [],
                'review_status' => 'pending',
            ],
        ];
    }

    private function initializeWorkflowFromPanel()
    {
        if (!$this->selectedPanel) return;

        $schema = $this->selectedPanel->model_schema ?? [];

        $this->workflowData = [
            'panel_setup' => [
                'name' => $this->selectedPanel->name,
                'slug' => $this->selectedPanel->slug,
                'description' => $this->selectedPanel->description,
                'icon' => $this->selectedPanel->icon,
                'navigation_group' => $this->selectedPanel->navigation_group,
                'navigation_sort' => $this->selectedPanel->navigation_sort,
                'is_active' => $this->selectedPanel->is_active,
            ],
            'model_design' => [
                'model_name' => $schema['model_name'] ?? Str::studly($this->selectedPanel->name),
                'table_name' => $schema['table_name'] ?? strtolower(str_replace(' ', '_', $this->selectedPanel->name)),
                'fields' => $this->convertFieldsToWorkflowFormat($this->selectedPanel->fields),
            ],
            'relations_setup' => [
                'relations' => $this->convertRelationsToWorkflowFormat($this->selectedPanel->relations),
            ],
            'resource_config' => [
                'resource_settings' => $this->selectedPanel->resource_config ?? [],
                'table_columns' => $this->generateTableColumnsFromFields($this->selectedPanel->fields),
                'form_fields' => $this->generateFormFieldsFromFields($this->selectedPanel->fields),
            ],
            'code_generation' => [
                'generated_files' => [],
                'review_status' => 'pending',
            ],
        ];
    }

    private function convertFieldsToWorkflowFormat($fields)
    {
        return $fields->map(function ($field) {
            return [
                'id' => $field->id,
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'filament_type' => $field->filament_type,
                'column_type' => $field->column_type,
                'nullable' => $field->nullable,
                'default' => $field->default,
                'order' => $field->order ?? 0,
            ];
        })->toArray();
    }

    private function convertRelationsToWorkflowFormat($relations)
    {
        return $relations->map(function ($relation) {
            return [
                'id' => $relation->id,
                'type' => $relation->type,
                'related_model' => $relation->related_model,
                'foreign_key' => $relation->foreign_key,
                'method_name' => $relation->method_name,
            ];
        })->toArray();
    }

    private function generateTableColumnsFromFields($fields)
    {
        return $fields->map(function ($field) {
            return [
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->column_type,
                'searchable' => true,
                'sortable' => true,
            ];
        })->toArray();
    }

    private function generateFormFieldsFromFields($fields)
    {
        return $fields->map(function ($field) {
            return [
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->filament_type,
                'required' => !$field->nullable,
                'validation' => $field->nullable ? [] : ['required'],
            ];
        })->toArray();
    }

    public function nextWorkflowStep()
    {
        if ($this->validateCurrentStep()) {
            $this->workflowHistory[] = [
                'step' => $this->workflowStep,
                'data' => $this->getCurrentStepData(),
                'timestamp' => now(),
            ];

            if ($this->workflowStep < count($this->workflowSteps)) {
                $this->workflowStep++;
            }
        }
    }

    public function previousWorkflowStep()
    {
        if ($this->workflowStep > 1) {
            $this->workflowStep--;
        }
    }

    public function completeWorkflowStep()
    {
        if ($this->validateCurrentStep()) {
            $this->nextWorkflowStep();
        }
    }

    public function cancelWorkflow()
    {
        $this->isWorkflowActive = false;
        $this->currentWorkflow = null;
        $this->workflowStep = 1;
        $this->workflowData = [];
        $this->workflowHistory = [];
        $this->selectedPanel = null;
    }

    private function validateCurrentStep()
    {
        $stepName = $this->workflowSteps[$this->workflowStep]['name'];

        switch ($stepName) {
            case 'panel_setup':
                return $this->validatePanelSetup();
            case 'model_design':
                return $this->validateModelDesign();
            case 'relations_setup':
                return $this->validateRelationsSetup();
            case 'resource_config':
                return $this->validateResourceConfig();
            case 'code_generation':
                return $this->validateCodeGeneration();
            default:
                return true;
        }
    }

    private function validatePanelSetup()
    {
        $data = $this->workflowData['panel_setup'];

        if (empty($data['name'])) {
            $this->addError('workflowData.panel_setup.name', 'Panel name is required');
            return false;
        }

        if (empty($data['slug'])) {
            $this->addError('workflowData.panel_setup.slug', 'Panel slug is required');
            return false;
        }

        return true;
    }

    private function validateModelDesign()
    {
        $data = $this->workflowData['model_design'];

        if (empty($data['model_name'])) {
            $this->addError('workflowData.model_design.model_name', 'Model name is required');
            return false;
        }

        if (empty($data['table_name'])) {
            $this->addError('workflowData.model_design.table_name', 'Table name is required');
            return false;
        }

        return true;
    }

    private function validateRelationsSetup()
    {
        // Relations are optional, so always return true
        return true;
    }

    private function validateResourceConfig()
    {
        // Resource config is optional, so always return true
        return true;
    }

    private function validateCodeGeneration()
    {
        return true;
    }

    private function getCurrentStepData()
    {
        $stepName = $this->workflowSteps[$this->workflowStep]['name'];
        return $this->workflowData[$stepName] ?? [];
    }

    public function saveWorkflowStepData($stepName, $data)
    {
        $this->workflowData[$stepName] = array_merge(
            $this->workflowData[$stepName] ?? [],
            $data
        );

        // Auto-generate slug from name if in panel setup
        if ($stepName === 'panel_setup' && isset($data['name'])) {
            $this->workflowData['panel_setup']['slug'] = Str::slug($data['name']);
        }

        // Auto-generate model and table names if in model design
        if ($stepName === 'model_design') {
            $panelName = $this->workflowData['panel_setup']['name'] ?? '';
            if (isset($data['model_name']) && !isset($data['table_name'])) {
                $this->workflowData['model_design']['table_name'] = strtolower(str_replace(' ', '_', $data['model_name']));
            } elseif (!isset($data['model_name']) && !empty($panelName)) {
                $this->workflowData['model_design']['model_name'] = Str::studly($panelName);
                $this->workflowData['model_design']['table_name'] = strtolower(str_replace(' ', '_', $panelName));
            }
        }
    }

    public function completeWorkflow()
    {
        if (!$this->validateCurrentStep()) {
            return;
        }

        try {
            // Save all workflow data
            $this->saveWorkflowData();

            // Generate code if needed
            if ($this->workflowData['code_generation']['review_status'] === 'approved') {
                $this->generateCode();
            }

            $this->cancelWorkflow();
            $this->loadPanels();

            session()->flash('message', 'Workflow completed successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error completing workflow: ' . $e->getMessage());
        }
    }

    private function saveWorkflowData()
    {
        $panelData = $this->workflowData['panel_setup'];
        $modelData = $this->workflowData['model_design'];
        $relationsData = $this->workflowData['relations_setup'];
        $resourceData = $this->workflowData['resource_config'];

        // Prepare model schema
        $modelSchema = [
            'model_name' => $modelData['model_name'],
            'table_name' => $modelData['table_name'],
            'fields' => $modelData['fields'],
            'relations' => $relationsData['relations'],
        ];

        // Create or update panel
        $panel = Panel::updateOrCreate(
            ['id' => $this->selectedPanel->id ?? null],
            [
                'name' => $panelData['name'],
                'slug' => $panelData['slug'],
                'description' => $panelData['description'],
                'icon' => $panelData['icon'],
                'navigation_group' => $panelData['navigation_group'],
                'navigation_sort' => $panelData['navigation_sort'],
                'model_schema' => $modelSchema,
                'resource_config' => $resourceData,
                'is_active' => $panelData['is_active'],
                'created_by' => auth()->id() ?? 1,
            ]
        );

        // Save fields
        $this->saveWorkflowFields($panel, $modelData['fields']);

        // Save relations
        $this->saveWorkflowRelations($panel, $relationsData['relations']);

        $this->selectedPanel = $panel;
    }

    private function saveWorkflowFields($panel, $fields)
    {
        // Delete existing fields if editing
        if ($this->currentWorkflow === 'edit') {
            $panel->fields()->delete();
        }

        foreach ($fields as $fieldData) {
            PanelField::create([
                'panel_id' => $panel->id,
                'name' => $fieldData['name'],
                'label' => $fieldData['label'],
                'type' => $fieldData['type'],
                'filament_type' => $fieldData['filament_type'],
                'column_type' => $fieldData['column_type'],
                'nullable' => $fieldData['nullable'] ?? false,
                'default' => $fieldData['default'] ?? null,
                'order' => $fieldData['order'] ?? 0,
            ]);
        }
    }

    private function saveWorkflowRelations($panel, $relations)
    {
        // Delete existing relations if editing
        if ($this->currentWorkflow === 'edit') {
            $panel->relations()->delete();
        }

        foreach ($relations as $relationData) {
            PanelRelation::create([
                'panel_id' => $panel->id,
                'type' => $relationData['type'],
                'related_model' => $relationData['related_model'],
                'foreign_key' => $relationData['foreign_key'] ?? null,
                'method_name' => $relationData['method_name'] ?? null,
            ]);
        }
    }

    private function generateCode()
    {
        if (!$this->selectedPanel) return;

        // Use existing code generation from Panel model
        $this->selectedPanel->generateModelCode();
        $this->selectedPanel->generateMigrationCode();
        $this->selectedPanel->generateResourceCode();
    }

    public function render()
    {
        return view('livewire.workflow-panel-manager', [
            'panels' => $this->panels,
            'selectedPanel' => $this->selectedPanel,
        ]);
    }
}
