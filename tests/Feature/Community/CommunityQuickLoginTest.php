<?php

namespace Tests\Feature\Community;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommunityQuickLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_login_page_exposes_the_three_quick_access_profiles(): void
    {
        $response = $this->get(route('comunigest.login'));

        $response->assertOk()
            ->assertSee('Acceso rápido')
            ->assertSee('admin@comunigest.test')
            ->assertSee('empleado@comunigest.test')
            ->assertSee('owner@comunigest.test')
            ->assertSee('data-quick-login', false);
    }

    public function test_admin_is_redirected_to_the_filament_community_dashboard(): void
    {
        $admin = $this->createUser('admin@comunigest.test', 'admin');

        $response = $this->post(route('comunigest.login'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('filament.app.pages.community-dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_employee_and_owner_are_redirected_to_the_unified_community_portal(): void
    {
        foreach ([
            ['empleado@comunigest.test', 'employee'],
            ['owner@comunigest.test', 'owner'],
        ] as [$email, $role]) {
            $user = $this->createUser($email, $role);

            $response = $this->post(route('comunigest.login'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response->assertRedirect(route('comunigest.inicio'));
            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    private function createUser(string $email, string $role): User
    {
        return User::query()->create([
            'name' => $role,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }
}
