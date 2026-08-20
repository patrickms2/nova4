<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Panel;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class ReactFlowEditor extends Component
{
    public $selectedPanel = null;
    public $flowData = [];
    public $nodes = [];
    public $edges = [];
    public $selectedNode = null;

    protected $listeners = [
        'panel-selected' => 'selectPanel',
        'flow-updated' => 'updateFlow',
        'node-selected' => 'selectNode',
    ];

    public function mount($panelId = null)
    {
        if ($panelId) {
            $this->selectPanel($panelId);
        }
    }

    public function selectPanel($panelId)
    {
        $this->selectedPanel = Panel::find($panelId);

        if ($this->selectedPanel) {
            // Load existing flow data if available
            $schema = $this->selectedPanel->model_schema ?? [];
            $this->flowData = $schema['flow_diagram'] ?? [];
            $this->nodes = $this->flowData['nodes'] ?? [];
            $this->edges = $this->flowData['edges'] ?? [];
        }
    }

    public function saveFlowDiagram()
    {
        if (!$this->selectedPanel) {
            session()->flash('error', 'Please select a panel first');
            return;
        }

        // Update panel schema with flow data
        $schema = $this->selectedPanel->model_schema ?? [];
        $schema['flow_diagram'] = [
            'nodes' => $this->nodes,
            'edges' => $this->edges,
            'viewport' => $this->flowData['viewport'] ?? [],
        ];

        // Convert flow nodes to panel fields and relations
        $this->convertFlowToPanelStructure($schema);

        $this->selectedPanel->update([
            'model_schema' => $schema
        ]);

        session()->flash('message', 'Flow diagram saved successfully!');
    }

    private function convertFlowToPanelStructure(&$schema)
    {
        $fields = [];
        $relations = [];

        foreach ($this->nodes as $node) {
            $nodeType = $node['type'] ?? '';
            $nodeData = $node['data'] ?? [];

            if (in_array($nodeType, ['text-input', 'textarea', 'select', 'checkbox', 'date-picker'])) {
                // Convert to field
                $fields[] = [
                    'name' => $nodeData['name'] ?? Str::slug($nodeData['label'] ?? 'field'),
                    'label' => $nodeData['label'] ?? 'Field',
                    'type' => $this->mapFieldType($nodeType),
                    'filament_type' => $this->mapFilamentFieldType($nodeType),
                    'column_type' => $this->mapColumnType($nodeType),
                    'nullable' => !($nodeData['required'] ?? false),
                    'default' => $nodeData['default'] ?? null,
                ];
            } elseif (in_array($nodeType, ['belongs-to', 'has-many', 'belongs-to-many'])) {
                // Convert to relation
                $relations[] = [
                    'type' => $nodeType,
                    'related_model' => $nodeData['model'] ?? 'RelatedModel',
                    'foreign_key' => $nodeData['foreign_key'] ?? null,
                    'method_name' => $nodeData['method_name'] ?? null,
                ];
            }
        }

        $schema['fields'] = $fields;
        $schema['relations'] = $relations;
    }

    private function mapFieldType($nodeType)
    {
        $mapping = [
            'text-input' => 'string',
            'textarea' => 'text',
            'select' => 'string',
            'checkbox' => 'boolean',
            'date-picker' => 'date',
        ];

        return $mapping[$nodeType] ?? 'string';
    }

    private function mapFilamentFieldType($nodeType)
    {
        $mapping = [
            'text-input' => 'TextInput',
            'textarea' => 'Textarea',
            'select' => 'Select',
            'checkbox' => 'Checkbox',
            'date-picker' => 'DatePicker',
        ];

        return $mapping[$nodeType] ?? 'TextInput';
    }

    private function mapColumnType($nodeType)
    {
        $mapping = [
            'text-input' => 'TextColumn',
            'textarea' => 'TextColumn',
            'select' => 'TextColumn',
            'checkbox' => 'CheckboxColumn',
            'date-picker' => 'DateColumn',
        ];

        return $mapping[$nodeType] ?? 'TextColumn';
    }

    public function clearFlowDiagram()
    {
        $this->nodes = [];
        $this->edges = [];
        $this->flowData = [];
        $this->selectedNode = null;

        session()->flash('message', 'Flow diagram cleared!');
    }

    public function exportFlow()
    {
        if (!$this->selectedPanel) {
            session()->flash('error', 'Please select a panel first');
            return;
        }

        $flowData = [
            'panel_id' => $this->selectedPanel->id,
            'panel_name' => $this->selectedPanel->name,
            'nodes' => $this->nodes,
            'edges' => $this->edges,
            'exported_at' => now()->toISOString(),
        ];

        // Create downloadable JSON
        $filename = 'flow-diagram-' . Str::slug($this->selectedPanel->name) . '.json';
        $content = json_encode($flowData, JSON_PRETTY_PRINT);

        // For now, just return the content as a flash message
        // In a real implementation, you'd create a proper download
        session()->flash('message', 'Flow exported! (File: ' . $filename . ')');
    }

    public function importFlow()
    {
        // This would handle file upload and import
        // For now, just show a placeholder
        session()->flash('message', 'Import feature coming soon!');
    }

    public function backToPanelManager()
    {
        return redirect()->route('panel-manager');
    }

    #[On('flow-updated')]
    public function updateFlow($nodes, $edges, $viewport = [])
    {
        $this->nodes = $nodes;
        $this->edges = $edges;
        $this->flowData['viewport'] = $viewport;
    }

    #[On('node-selected')]
    public function selectNode($nodeId)
    {
        $this->selectedNode = collect($this->nodes)->firstWhere('id', $nodeId);
    }

    public function getPanelsProperty()
    {
        return Panel::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.react-flow-editor')->layout('layouts.app');
    }
}
