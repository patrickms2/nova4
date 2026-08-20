<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Panel;

class ReactFlowBuilder extends Component
{
    public $panels = [];
    public $currentPanelId = null;

    protected $listeners = [
        'panel-updated' => 'refreshPanels',
        'flow-saved' => 'handleFlowSaved',
    ];

    public function mount()
    {
        $this->refreshPanels();
    }

    public function refreshPanels()
    {
        $this->panels = Panel::orderBy('navigation_group')
            ->orderBy('navigation_sort')
            ->get();
    }

    public function handleFlowSaved($data)
    {
        // Handle flow saved event
        $this->dispatch('show-notification', [
            'type' => 'success',
            'message' => 'Flow saved successfully!'
        ]);
    }

    public function render()
    {
        return view('livewire.react-flow-builder', [
            'panels' => $this->panels,
        ]);
    }
}
