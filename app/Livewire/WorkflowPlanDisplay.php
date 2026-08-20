<?php

namespace App\Livewire;

use Livewire\Component;

class WorkflowPlanDisplay extends Component
{
    public array $workflowPlan = [];

    public bool $expanded = true;

    public function mount(array $workflowPlan): void
    {
        $this->workflowPlan = $workflowPlan;
    }

    public function toggleExpanded(): void
    {
        $this->expanded = ! $this->expanded;
    }

    public function getPlanTypeProperty(): string
    {
        return $this->workflowPlan['type'] ?? 'workflow_plan';
    }

    public function getStrategyTypeProperty(): string
    {
        return $this->workflowPlan['strategy_type'] ?? 'single_tool';
    }

    public function getEstimatedDurationProperty(): int
    {
        return $this->workflowPlan['estimated_duration_seconds'] ?? 5;
    }

    public function getTotalStagesProperty(): int
    {
        return count($this->workflowPlan['stages'] ?? []);
    }

    public function render()
    {
        return view('livewire.workflow-plan-display');
    }
}
