<?php

namespace App\Filament\Resources\AgentWorkflows\Pages;

use Filament\Support\Icons\Heroicon;

use Filament\Actions;
use Filament\Notifications\Notification;
use App\Filament\Resources\AgentWorkflows\AgentWorkflowResource;
use Heiner\FilamentAgenticChatbot\Filament\Resources\AgentWorkflows\Pages\EditAgentWorkflow as BaseEditAgentWorkflow;
use Heiner\FilamentAgenticChatbot\Support\UiText;

class EditAgentWorkflow extends BaseEditAgentWorkflow
{
    protected function getHeaderActions(): array
    {
        $actions = parent::getHeaderActions();

        // Add Test Workflow action before the Options group
        $testAction = Actions\Action::make('testWorkflow')
            ->label('Test Workflow')
            ->icon(Heroicon::OutlinedPlay)
            ->color('primary')
            ->url(fn () => route('filament.admin.pages.workflow-chat', [
                'workflow' => $this->record,
                'bot' => $this->workflow->rag_bot_id,
            ]))
            ->openUrlInNewTab();

        // Insert test action before the last action (the Options group)
        array_splice($actions, -1, 0, [$testAction]);

        return $actions;
    }
}
