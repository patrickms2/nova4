<?php

namespace App\Livewire\WorkflowSteps;

use Livewire\Component;
use Illuminate\Support\Str;

class ModelDesignStep extends Component
{
    public $workflowData;
    public $stepValidation = [];
    public $newField = [];

    protected $rules = [
        'workflowData.model_design.model_name' => 'required|string|max:255',
        'workflowData.model_design.table_name' => 'required|string|max:255',
    ];

    public function mount($workflowData = [])
    {
        $this->workflowData = $workflowData;
        $this->initializeNewField();
    }

    private function initializeNewField()
    {
        $this->newField = [
            'name' => '',
            'label' => '',
            'type' => 'string',
            'filament_type' => 'TextInput',
            'column_type' => 'TextColumn',
            'nullable' => false,
            'default' => '',
            'order' => count($this->workflowData['model_design']['fields'] ?? []),
        ];
    }

    public function updatedWorkflowDataModelDesignModelName($value)
    {
        if (empty($this->workflowData['model_design']['table_name'])) {
            $this->workflowData['model_design']['table_name'] = strtolower(str_replace(' ', '_', $value));
        }
    }

    public function addField()
    {
        $this->validate([
            'newField.name' => 'required|string|max:255',
            'newField.label' => 'required|string|max:255',
            'newField.type' => 'required|string',
            'newField.filament_type' => 'required|string',
            'newField.column_type' => 'required|string',
        ]);

        // Check for duplicate field names
        $existingFields = collect($this->workflowData['model_design']['fields'] ?? []);
        if ($existingFields->pluck('name')->contains($this->newField['name'])) {
            $this->stepValidation['field_name'] = 'Field name already exists';
            return;
        }

        $this->workflowData['model_design']['fields'][] = $this->newField;
        $this->initializeNewField();
        $this->stepValidation = [];
    }

    public function removeField($index)
    {
        unset($this->workflowData['model_design']['fields'][$index]);
        $this->workflowData['model_design']['fields'] = array_values($this->workflowData['model_design']['fields']);
    }

    public function moveFieldUp($index)
    {
        if ($index > 0) {
            $fields = $this->workflowData['model_design']['fields'];
            $temp = $fields[$index];
            $fields[$index] = $fields[$index - 1];
            $fields[$index - 1] = $temp;
            $this->workflowData['model_design']['fields'] = $fields;
        }
    }

    public function moveFieldDown($index)
    {
        $fields = $this->workflowData['model_design']['fields'] ?? [];
        if ($index < count($fields) - 1) {
            $temp = $fields[$index];
            $fields[$index] = $fields[$index + 1];
            $fields[$index + 1] = $temp;
            $this->workflowData['model_design']['fields'] = $fields;
        }
    }

    public function validateStep()
    {
        $this->validate();

        if (empty($this->workflowData['model_design']['model_name'])) {
            $this->stepValidation['model_name'] = 'Model name is required';
            return false;
        }

        if (empty($this->workflowData['model_design']['table_name'])) {
            $this->stepValidation['table_name'] = 'Table name is required';
            return false;
        }

        $this->stepValidation = [];
        return true;
    }

    public function getStepData()
    {
        return $this->workflowData['model_design'] ?? [];
    }

    public function render()
    {
        $availableFieldTypes = [
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

        $availableFilamentFieldTypes = [
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

        $availableColumnTypes = [
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

        return view('livewire.workflow-steps.model-design-step', [
            'availableFieldTypes' => $availableFieldTypes,
            'availableFilamentFieldTypes' => $availableFilamentFieldTypes,
            'availableColumnTypes' => $availableColumnTypes,
        ]);
    }
}
