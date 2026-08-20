<?php

namespace App\Livewire;

use Heiner\FilamentAgenticChatbot\Models\AgentWorkflow;
use Heiner\FilamentAgenticChatbot\Models\RagBot;
use App\Models\Server;
use Heiner\FilamentAgenticChatbot\Models\RagConversation;
use Heiner\FilamentAgenticChatbot\Services\WorkflowRunner;
use Livewire\Component;

class WorkflowChat extends Component
{
    public ?int $workflowId = null;

    public ?AgentWorkflow $workflow = null;

    public ?int $botId = null;

    public ?RagBot $bot = null;

    public string $message = '';

    public array $messages = [];

    public bool $isLoading = false;

    public ?string $error = null;

    public array $executionSteps = [];

    public string $sessionId = '';

    public function mount(?int $workflowId = null, ?int $botId = null): void
    {
        $this->workflowId = $workflowId;
        $this->botId = $botId;
        $this->sessionId = 'test-' . uniqid();
        $this->loadWorkflow();
    }

    public function loadWorkflow(): void
    {
        $this->workflow = AgentWorkflow::query()->find($this->workflowId);

        if ($this->botId) {
            $this->bot = RagBot::query()->find($this->botId);
        } elseif ($this->workflow) {
            $this->bot = $this->workflow->ragBot;
        }
    }
    
    public function loadBot(): void
    {
        if ($this->botId) {
            $this->bot = RagBot::query()->find($this->botId);
        }
    }
    
    
    public function send(): void
    {
        $this->validate([
            'message' => ['required', 'string', 'max:10000'],
        ]);

        if (!$this->workflow) {
            $this->error = 'Workflow not found';
            return;
        }

        if (!$this->bot) {
            $this->error = 'Bot not found. Please assign a bot to this workflow.';
            return;
        }

        $userMessage = trim($this->message);

        $this->messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        $this->message = '';
        $this->isLoading = true;
        $this->error = null;
        $this->executionSteps = [];

        try {
            // Get or create conversation
            $ragConversation = RagConversation::firstOrCreate(
                [
                    'rag_bot_id' => $this->bot->id,
                    'session_id' => $this->sessionId,
                ],
                [
                    'context_area' => 'workflow_test',
                    'meta' => [
                        'test_mode' => true,
                        'workflow_id' => $this->workflow->id,
                    ],
                ]
            );

            // Prepare initial variables
            $initialVariables = [
                'user_message' => $userMessage,
                'test_mode' => true,
            ];

            // Execute workflow
            $runner = app(WorkflowRunner::class);
            $state = $runner->start(
                workflow: $this->workflow,
                conversation: $ragConversation,
                userInput: $userMessage,
                initialVariables: $initialVariables,
            );

            $this->messages[] = [
                'role' => 'assistant',
                'content' => $state->output ?? '',
                'halted' => $state->halted,
                'halt_reason' => $state->haltReason,
            ];

            $this->executionSteps = [
                'workflow_id' => $this->workflow->id,
                'workflow_name' => $this->workflow->name,
                'halted' => $state->halted,
                'halt_reason' => $state->haltReason,
                'variables' => $state->variables,
            ];
        } catch (\Throwable $exception) {
            $this->error = 'Workflow execution failed: ' . $exception->getMessage();
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Error: ' . $exception->getMessage(),
            ];
        }

        $this->isLoading = false;
    }

    public function resetChat(): void
    {
        $this->messages = [];
        $this->error = null;
        $this->executionSteps = [];
        $this->sessionId = 'test-' . uniqid();
    }

    public function render()
    {
        return view('livewire.workflow-chat');
    }
}
