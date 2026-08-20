<?php

namespace Tests\Unit\Services;

use App\Services\ClickUp\ClickUpService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClickUpServiceTest extends TestCase
{
    public function test_it_creates_a_task_with_the_configured_workspace_and_list(): void
    {
        config()->set('services.clickup.api_token', 'token-123');
        config()->set('services.clickup.workspace_id', 'workspace-123');
        config()->set('services.clickup.default_list_id', 'list-123');

        Http::fake([
            'https://api.clickup.com/api/v2/list/list-123/task' => Http::response([
                'id' => 'task-123',
                'name' => 'Nueva tarea',
                'status' => ['status' => 'open'],
                'url' => 'https://app.clickup.com/t/task-123',
            ], 200),
        ]);

        $service = new ClickUpService();

        $result = $service->createTask([
            'name' => 'Nueva tarea',
            'description' => 'Descripción de prueba',
        ]);

        $this->assertSame('task-123', $result['id']);
        $this->assertSame('Nueva tarea', $result['name']);
        $this->assertSame('open', $result['status']);
        $this->assertSame('https://app.clickup.com/t/task-123', $result['url']);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'token-123')
                && $request->url() === 'https://api.clickup.com/api/v2/list/list-123/task';
        });
    }
}
