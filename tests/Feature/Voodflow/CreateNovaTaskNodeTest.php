<?php

namespace Tests\Feature\Voodflow;

use App\Domain\TaskManagement\Enums\TaskPriority;
use App\Domain\TaskManagement\Enums\TaskStatus;
use App\Domain\TaskManagement\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Voodflow\Voodflow\Execution\ExecutionContext;
use Voodflow\Voodflow\Models\Execution;
use Voodflow\Voodflow\Models\Node;
use Voodflow\Voodflow\Nodes\CreateNovaTaskNode\CreateNovaTaskNode;

/**
 * Regression test for the CreateNovaTaskNode Voodflow custom node.
 *
 * Verifies the node is a thin adapter over the existing
 * App\Domain\TaskManagement\Actions\CreateTaskAction — it must create a
 * real Task through that Action, with zero duplicated business logic.
 * See .codex/tasks/029-NOVA Voodflow Action Nodes.md.
 *
 * The node class is not on any PSR-4 autoload path (custom Voodflow
 * nodes live under storage/voodflow-nodes/, the path
 * Voodflow\Voodflow\Services\NodeRegistry actually scans — see the
 * mission's completion report), so it is required manually here.
 */
class CreateNovaTaskNodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        require_once base_path('storage/voodflow-nodes/CreateNovaTaskNode/CreateNovaTaskNode.php');
    }

    private function makeContext(array $config, array $input = []): ExecutionContext
    {
        $execution = new Execution(['context' => []]);
        $node = new Node(['type' => CreateNovaTaskNode::type()]);

        return new ExecutionContext(
            execution: $execution,
            node: $node,
            input: $input,
            config: $config,
            eventClass: 'test.event',
        );
    }

    public function test_it_creates_a_task_through_the_existing_action(): void
    {
        $context = $this->makeContext([
            'title' => 'Follow up with guest',
            'description_field' => 'Sent via automation',
            'due_date' => '2026-08-15',
            'estimated_hours' => '2',
            'priority' => 'high',
        ]);

        $result = (new CreateNovaTaskNode)->execute($context);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('tasks', [
            'title' => 'Follow up with guest',
            'description' => 'Sent via automation',
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::Todo->value,
            'estimated_hours' => 2,
        ]);

        $task = Task::where('title', 'Follow up with guest')->firstOrFail();
        $this->assertSame($task->id, $result->output['nova_task_id']);
        $this->assertSame(TaskStatus::Todo->value, $result->output['nova_task_status']);
    }

    public function test_it_resolves_tags_from_upstream_node_output(): void
    {
        $context = $this->makeContext(
            config: [
                'title' => '{{title}}',
                'due_date' => '{{due_date}}',
                'estimated_hours' => '1',
                'priority' => 'medium',
            ],
            input: ['title' => 'Reservation confirmed', 'due_date' => '2026-09-01'],
        );

        $result = (new CreateNovaTaskNode)->execute($context);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('tasks', ['title' => 'Reservation confirmed']);
    }

    public function test_it_fails_without_business_impact_when_title_is_missing(): void
    {
        $context = $this->makeContext([
            'title' => '',
            'due_date' => '2026-08-15',
        ]);

        $result = (new CreateNovaTaskNode)->execute($context);

        $this->assertFalse($result->success);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_it_fails_when_due_date_cannot_be_parsed(): void
    {
        $context = $this->makeContext([
            'title' => 'Some task',
            'due_date' => 'not-a-date',
        ]);

        $result = (new CreateNovaTaskNode)->execute($context);

        $this->assertFalse($result->success);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_validate_reports_missing_required_fields(): void
    {
        $errors = (new CreateNovaTaskNode)->validate([]);

        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('due_date', $errors);
    }

    public function test_validate_reports_invalid_priority(): void
    {
        $errors = (new CreateNovaTaskNode)->validate([
            'title' => 'x',
            'due_date' => '2026-08-15',
            'priority' => 'urgent',
        ]);

        $this->assertArrayHasKey('priority', $errors);
    }
}
