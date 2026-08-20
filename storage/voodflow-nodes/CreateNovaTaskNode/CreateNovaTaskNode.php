<?php

namespace Voodflow\Voodflow\Nodes\CreateNovaTaskNode;

use App\Domain\TaskManagement\Actions\CreateTaskAction;
use App\Domain\TaskManagement\Enums\TaskPriority;
use Illuminate\Support\Carbon;
use Voodflow\Voodflow\Contracts\NodeInterface;
use Voodflow\Voodflow\Execution\ExecutionContext;
use Voodflow\Voodflow\Execution\ExecutionResult;
use Voodflow\Voodflow\Support\TagReplacer;

/**
 * Create Nova Task
 *
 * Thin execution adapter over the existing
 * App\Domain\TaskManagement\Actions\CreateTaskAction. This node must
 * never contain independent business logic — it only maps configured
 * fields (with {{template}} resolution against the upstream node's
 * output) onto the Action's existing execute() signature and calls it
 * directly. See .codex/tasks/029-NOVA Voodflow Action Nodes.md.
 *
 * All metadata (name, description, author, version, color, icon, etc.)
 * is defined in manifest.json to avoid duplication.
 *
 * @author Voodflow
 *
 * @version 1.0.0
 */
class CreateNovaTaskNode implements NodeInterface
{
    public static function type(): string
    {
        return 'create_nova_task_node';
    }

    public static function defaultConfig(): array
    {
        return [
            'label' => 'Create Nova Task',
            'description' => '',
            'title' => '',
            'description_field' => '',
            'due_date' => '',
            'estimated_hours' => '1',
            'priority' => TaskPriority::Medium->value,
        ];
    }

    /**
     * Execute the node logic
     */
    public function execute(ExecutionContext $context): ExecutionResult
    {
        $tagContext = $context->getFullContext();

        $title = TagReplacer::replace((string) $context->getConfig('title', ''), $tagContext);
        $dueDateRaw = TagReplacer::replace((string) $context->getConfig('due_date', ''), $tagContext);
        $description = TagReplacer::replace((string) $context->getConfig('description_field', ''), $tagContext);
        $estimatedHours = (int) $context->getConfig('estimated_hours', 1);
        $priority = TaskPriority::tryFrom((string) $context->getConfig('priority', TaskPriority::Medium->value))
            ?? TaskPriority::Medium;

        if ($title === '' || $dueDateRaw === '') {
            return ExecutionResult::failure('Title and due date are required to create a NOVA task.');
        }

        try {
            $dueDate = Carbon::parse($dueDateRaw);
        } catch (\Throwable) {
            return ExecutionResult::failure("Due date '{$dueDateRaw}' could not be parsed.");
        }

        $task = app(CreateTaskAction::class)->execute(
            title: $title,
            dueDate: $dueDate,
            estimatedHours: $estimatedHours,
            priority: $priority,
            description: $description !== '' ? $description : null,
        );

        return ExecutionResult::success(array_merge($context->input, [
            'nova_task_id' => $task->id,
            'nova_task_title' => $task->title,
            'nova_task_status' => $task->status->value,
        ]));
    }

    /**
     * Validate node configuration
     */
    public function validate(array $config): array
    {
        $errors = [];

        if (empty($config['title'])) {
            $errors['title'] = 'Title is required';
        }

        if (empty($config['due_date'])) {
            $errors['due_date'] = 'Due date is required';
        }

        if (isset($config['priority']) && TaskPriority::tryFrom((string) $config['priority']) === null) {
            $errors['priority'] = 'Priority must be one of: '.implode(', ', array_column(TaskPriority::cases(), 'value'));
        }

        return $errors;
    }

    /**
     * Get node definition (UI configuration fields)
     */
    public static function definition(): array
    {
        return [
            ['key' => 'title', 'type' => 'text', 'label' => 'Title', 'required' => true],
            ['key' => 'description_field', 'type' => 'textarea', 'label' => 'Description', 'required' => false],
            ['key' => 'due_date', 'type' => 'text', 'label' => 'Due Date', 'required' => true],
            ['key' => 'estimated_hours', 'type' => 'text', 'label' => 'Estimated Hours', 'required' => true],
            [
                'key' => 'priority',
                'type' => 'select',
                'label' => 'Priority',
                'required' => true,
                'options' => array_map(
                    static fn (TaskPriority $priority): array => ['value' => $priority->value, 'label' => ucfirst($priority->value)],
                    TaskPriority::cases(),
                ),
            ],
        ];
    }

    /**
     * Whether this node supports retry on failure
     */
    public static function supportsRetry(): bool
    {
        return true;
    }
}
