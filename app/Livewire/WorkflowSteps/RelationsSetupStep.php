<?php

namespace App\Livewire\WorkflowSteps;

use Livewire\Component;
use Illuminate\Support\Str;

class RelationsSetupStep extends Component
{
    public $workflowData;
    public $stepValidation = [];
    public $newRelation = [];

    protected $rules = [
        'newRelation.type' => 'required|string',
        'newRelation.related_model' => 'required|string',
        'newRelation.foreign_key' => 'nullable|string',
        'newRelation.method_name' => 'nullable|string',
    ];

    public function mount($workflowData = [])
    {
        $this->workflowData = $workflowData;
        $this->initializeNewRelation();
    }

    private function initializeNewRelation()
    {
        $this->newRelation = [
            'type' => 'belongsTo',
            'related_model' => '',
            'foreign_key' => '',
            'method_name' => '',
        ];
    }

    public function updatedWorkflowDataRelationsSetupRelationType($value)
    {
        // Auto-generate method name based on relation type
        if (!empty($this->newRelation['related_model'])) {
            $modelName = $this->newRelation['related_model'];

            switch ($value) {
                case 'belongsTo':
                    $this->newRelation['method_name'] = strtolower($modelName);
                    $this->newRelation['foreign_key'] = strtolower($modelName) . '_id';
                    break;
                case 'hasMany':
                    $this->newRelation['method_name'] = strtolower(Str::plural($modelName));
                    break;
                case 'hasOne':
                    $this->newRelation['method_name'] = strtolower($modelName);
                    break;
                case 'belongsToMany':
                    $this->newRelation['method_name'] = strtolower(Str::plural($modelName));
                    break;
                case 'morphMany':
                    $this->newRelation['method_name'] = strtolower(Str::plural($modelName));
                    break;
                case 'morphOne':
                    $this->newRelation['method_name'] = strtolower($modelName);
                    break;
            }
        }
    }

    public function updatedWorkflowDataRelationsSetupRelatedModel($value)
    {
        if (!empty($value) && !empty($this->newRelation['type'])) {
            $this->updatedWorkflowDataRelationsSetupRelationType($this->newRelation['type']);
        }
    }

    public function addRelation()
    {
        $this->validate();

        // Check for duplicate relation method names
        $existingRelations = collect($this->workflowData['relations_setup']['relations'] ?? []);
        if ($existingRelations->pluck('method_name')->contains($this->newRelation['method_name'])) {
            $this->stepValidation['relation_method'] = 'Relation method name already exists';
            return;
        }

        $this->workflowData['relations_setup']['relations'][] = $this->newRelation;
        $this->initializeNewRelation();
        $this->stepValidation = [];
    }

    public function removeRelation($index)
    {
        unset($this->workflowData['relations_setup']['relations'][$index]);
        $this->workflowData['relations_setup']['relations'] = array_values($this->workflowData['relations_setup']['relations']);
    }

    public function moveRelationUp($index)
    {
        if ($index > 0) {
            $relations = $this->workflowData['relations_setup']['relations'];
            $temp = $relations[$index];
            $relations[$index] = $relations[$index - 1];
            $relations[$index - 1] = $temp;
            $this->workflowData['relations_setup']['relations'] = $relations;
        }
    }

    public function moveRelationDown($index)
    {
        $relations = $this->workflowData['relations_setup']['relations'] ?? [];
        if ($index < count($relations) - 1) {
            $temp = $relations[$index];
            $relations[$index] = $relations[$index + 1];
            $relations[$index + 1] = $temp;
            $this->workflowData['relations_setup']['relations'] = $relations;
        }
    }

    public function validateStep()
    {
        // Relations are optional, so always return true
        $this->stepValidation = [];
        return true;
    }

    public function getStepData()
    {
        return $this->workflowData['relations_setup'] ?? [];
    }

    public function render()
    {
        $availableRelationTypes = [
            'belongsTo' => 'Belongs To',
            'hasMany' => 'Has Many',
            'hasOne' => 'Has One',
            'belongsToMany' => 'Belongs To Many',
            'morphMany' => 'Morph Many',
            'morphOne' => 'Morph One',
        ];

        // Get available models from the app
        $availableModels = $this->getAvailableModels();

        return view('livewire.workflow-steps.relations-setup-step', [
            'availableRelationTypes' => $availableRelationTypes,
            'availableModels' => $availableModels,
        ]);
    }

    private function getAvailableModels()
    {
        $models = [];

        // Common Laravel models
        $commonModels = [
            'User',
            'Role',
            'Permission',
            'Category',
            'Tag',
            'Comment',
            'Post',
            'Page',
            'Media',
            'Setting',
        ];

        // Try to get models from the app directory
        try {
            $modelPath = app_path('Models');
            if (is_dir($modelPath)) {
                $files = glob($modelPath . '/*.php');
                foreach ($files as $file) {
                    $modelName = basename($file, '.php');
                    if ($modelName !== 'Panel' && $modelName !== 'PanelField' && $modelName !== 'PanelRelation' && $modelName !== 'PanelTable') {
                        $models[] = $modelName;
                    }
                }
            }
        } catch (\Exception $e) {
            // Fallback to common models if directory scan fails
            $models = $commonModels;
        }

        // Merge with common models and remove duplicates
        $models = array_unique(array_merge($models, $commonModels));
        sort($models);

        return $models;
    }
}
