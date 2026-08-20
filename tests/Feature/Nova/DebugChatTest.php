<?php

declare(strict_types=1);

namespace Tests\Feature\Nova;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class DebugChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_debug_chat_page_requires_authentication(): void
    {
        $response = $this->get(route('nova.debug.chat'));

        $response->assertRedirect(route('login'));
    }

    public function test_debug_chat_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('nova.debug.chat'));

        $response->assertOk();
        $response->assertSee('NOVA Debug Chat');
    }

    public function test_debug_chat_send_returns_full_kernel_result(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('nova.debug.chat.send'), [
                'message' => 'hola',
                'channel' => 'debug',
                'user' => 'debug-tester',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('reply', fn (string $reply): bool => str_contains($reply, 'Soy el Copiloto'));
        $response->assertJsonPath('input.type', 'text');
        $response->assertJsonPath('intent.name', 'greeting');
        $response->assertJsonPath('context.phone', 'debug-tester');
        $this->assertNotEmpty($response->json('pipeline'));
        $this->assertArrayHasKey('timings', $response->json());
    }

    public function test_debug_chat_handles_operational_intent(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('nova.debug.chat.send'), [
                'message' => 'He comprado pintura por 42€',
                'channel' => 'debug',
                'user' => 'debug-tester',
            ]);

        $response->assertOk();
        $response->assertJsonPath('intent.name', 'create');
        $response->assertJsonPath('intent.target_capability', 'expenses');
        $this->assertNotNull($response->json('context.active_conversation'));
    }
}
