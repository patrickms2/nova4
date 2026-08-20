<?php

namespace App\Livewire\WorkflowSteps;

use Livewire\Component;
use Illuminate\Support\Str;

class PanelSetupStep extends Component
{
    public $workflowData;
    public $stepValidation = [];

    protected $rules = [
        'workflowData.panel_setup.name' => 'required|string|max:255',
        'workflowData.panel_setup.slug' => 'required|string|max:255',
        'workflowData.panel_setup.description' => 'nullable|string|max:65535',
        'workflowData.panel_setup.icon' => 'required|string',
        'workflowData.panel_setup.navigation_group' => 'nullable|string|max:255',
        'workflowData.panel_setup.navigation_sort' => 'nullable|integer|min:0',
        'workflowData.panel_setup.is_active' => 'boolean',
    ];

    public function mount($workflowData = [])
    {
        $this->workflowData = $workflowData;
    }

    public function updatedWorkflowDataPanelSetupName($value)
    {
        $this->workflowData['panel_setup']['slug'] = Str::slug($value);
    }

    public function validateStep()
    {
        $this->validate();

        // Additional custom validations
        if (empty($this->workflowData['panel_setup']['name'])) {
            $this->stepValidation['name'] = 'Panel name is required';
            return false;
        }

        if (empty($this->workflowData['panel_setup']['slug'])) {
            $this->stepValidation['slug'] = 'Panel slug is required';
            return false;
        }

        $this->stepValidation = [];
        return true;
    }

    public function getStepData()
    {
        return $this->workflowData['panel_setup'] ?? [];
    }

    public function render()
    {
        return view('livewire.workflow-steps.panel-setup-step');
    }
}
