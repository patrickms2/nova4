<?php

namespace App\Filament\Resources\AgentWorkflows;

use App\Filament\Resources\AgentWorkflows\Pages\EditAgentWorkflow;
use App\Filament\Resources\AgentWorkflows\Pages\ListAgentWorkflows;
use Heiner\FilamentAgenticChatbot\Filament\Resources\AgentWorkflows\AgentWorkflowResource as BaseAgentWorkflowResource;
use Heiner\FilamentAgenticChatbot\Filament\Resources\AgentWorkflows\Pages\CreateAgentWorkflow;

class AgentWorkflowResource extends BaseAgentWorkflowResource
{
    public static function getPages(): array
    {
        return [
            'index' => ListAgentWorkflows::route('/'),
            'create' => CreateAgentWorkflow::route('/create'),
            //'edit' => EditAgentWorkflow::route('/{record}/edit'),
        ];
    }
}
