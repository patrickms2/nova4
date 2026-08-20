<?php

namespace App\Livewire\WorkflowSteps;

use Livewire\Component;

class ResourceConfigStep extends Component
{
    public $workflowData;
    public $stepValidation = [];
    public $newFormField = [];
    public $newTableColumn = [];

    protected $rules = [
        'newFormField.name' => 'required|string|max:255',
        'newFormField.label' => 'required|string|max:255',
        'newFormField.type' => 'required|string',
        'newTableColumn.name' => 'required|string|max:255',
        'newTableColumn.label' => 'required|string|max:255',
        'newTableColumn.type' => 'required|string',
    ];

    public function mount($workflowData = [])
    {
        $this->workflowData = $workflowData;
        $this->initializeNewFormFields();
        $this->initializeNewTableColumns();
    }

    private function initializeNewFormFields()
    {
        $this->newFormField = [
            'name' => '',
            'label' => '',
            'type' => 'TextInput',
            'required' => false,
            'validation' => [],
        ];
    }

    private function initializeNewTableColumns()
    {
        $this->newTableColumn = [
            'name' => '',
            'label' => '',
            'type' => 'TextColumn',
            'searchable' => true,
            'sortable' => true,
        ];
    }

    public function addFormField()
    {
        $this->validate();

        // Check for duplicate form field names
        $existingFormFields = collect($this->workflowData['resource_config']['form_fields'] ?? []);
        if ($existingFormFields->pluck('name')->contains($this->newFormField['name'])) {
            $this->stepValidation['form_field_name'] = 'Form field name already exists';
            return;
        }

        $this->workflowData['resource_config']['form_fields'][] = $this->newFormField;
        $this->initializeNewFormFields();
        $this->stepValidation = [];
    }

    public function removeFormField($index)
    {
        unset($this->workflowData['resource_config']['form_fields'][$index]);
        $this->workflowData['resource_config']['form_fields'] = array_values($this->workflowData['resource_config']['form_fields']);
    }

    public function moveFormFieldUp($index)
    {
        if ($index > 0) {
            $formFields = $this->workflowData['resource_config']['form_fields'];
            $temp = $formFields[$index];
            $formFields[$index] = $formFields[$index - 1];
            $formFields[$index - 1] = $temp;
            $this->workflowData['resource_config']['form_fields'] = $formFields;
        }
    }

    public function moveFormFieldDown($index)
    {
        $formFields = $this->workflowData['resource_config']['form_fields'] ?? [];
        if ($index < count($formFields) - 1) {
            $temp = $formFields[$index];
            $formFields[$index] = $formFields[$index + 1];
            $formFields[$index + 1] = $temp;
            $this->workflowData['resource_config']['form_fields'] = $formFields;
        }
    }

    public function addTableColumn()
    {
        $this->validate();

        // Check for duplicate table column names
        $existingTableColumns = collect($this->workflowData['resource_config']['table_columns'] ?? []);
        if ($existingTableColumns->pluck('name')->contains($this->newTableColumn['name'])) {
            $this->stepValidation['table_column_name'] = 'Table column name already exists';
            return;
        }

        $this->workflowData['resource_config']['table_columns'][] = $this->newTableColumn;
        $this->initializeNewTableColumns();
        $this->stepValidation = [];
    }

    public function removeTableColumn($index)
    {
        unset($this->workflowData['resource_config']['table_columns'][$index]);
        $this->workflowData['resource_config']['table_columns'] = array_values($this->workflowData['resource_config']['table_columns']);
    }

    public function moveTableColumnUp($index)
    {
        if ($index > 0) {
            $tableColumns = $this->workflowData['resource_config']['table_columns'];
            $temp = $tableColumns[$index];
            $tableColumns[$index] = $tableColumns[$index - 1];
            $tableColumns[$index - 1] = $temp;
            $this->workflowData['resource_config']['table_columns'] = $tableColumns;
        }
    }

    public function moveTableColumnDown($index)
    {
        $tableColumns = $this->workflowData['resource_config']['table_columns'] ?? [];
        if ($index < count($tableColumns) - 1) {
            $temp = $tableColumns[$index];
            $tableColumns[$index] = $tableColumns[$index + 1];
            $tableColumns[$index + 1] = $temp;
            $this->workflowData['resource_config']['table_columns'] = $tableColumns;
        }
    }

    public function autoGenerateFromModelFields()
    {
        $modelFields = $this->workflowData['model_design']['fields'] ?? [];

        // Auto-generate form fields from model fields
        $formFields = [];
        foreach ($modelFields as $field) {
            $formFields[] = [
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['filament_type'],
                'required' => !$field['nullable'],
                'validation' => $field['nullable'] ? [] : ['required'],
            ];
        }

        // Auto-generate table columns from model fields
        $tableColumns = [];
        foreach ($modelFields as $field) {
            $tableColumns[] = [
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['column_type'],
                'searchable' => true,
                'sortable' => true,
            ];
        }

        $this->workflowData['resource_config']['form_fields'] = $formFields;
        $this->workflowData['resource_config']['table_columns'] = $tableColumns;
    }

    public function validateStep()
    {
        // Resource config is optional, so always return true
        $this->stepValidation = [];
        return true;
    }

    public function getStepData()
    {
        return $this->workflowData['resource_config'] ?? [];
    }

    public function render()
    {
        $availableFormFieldTypes = [
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

        $availableTableColumnTypes = [
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

        return view('livewire.workflow-steps.resource-config-step', [
            'availableFormFieldTypes' => $availableFormFieldTypes,
            'availableTableColumnTypes' => $availableTableColumnTypes,
        ]);
    }
}
