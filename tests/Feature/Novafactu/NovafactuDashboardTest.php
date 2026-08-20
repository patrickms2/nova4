<?php

namespace Tests\Feature\Novafactu;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NovafactuDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_novafactu_dashboard_requires_authentication(): void
    {
        $this->get(route('novafactu.dashboard'))
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_novafactu_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('novafactu.dashboard'))
            ->assertStatus(200)
            ->assertSee('NovaFactu')
            ->assertSee('Panel de control');
    }
}
