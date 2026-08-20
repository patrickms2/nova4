<?php

namespace Tests\Feature\Community;

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\CommunityPortalContext;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CommunityPortalTest extends TestCase
{
    public function test_employee_context_uses_existing_user_and_employee_models(): void
    {
        $user = new User(['role' => 'employee']);
        $user->setRelation('employee', new Employee(['name' => 'Empleado Community']));

        $this->assertTrue(CommunityPortalContext::isEmployee($user));
        $this->assertSame('employee', CommunityPortalContext::portalType($user));
    }

    public function test_work_orders_expose_community_shift_assignments(): void
    {
        $this->assertInstanceOf(HasMany::class, (new WorkOrder)->communityShifts());
    }

    public function test_comunigest_field_routes_require_authentication(): void
    {
        foreach (['comunigest.inicio', 'comunigest.work-order', 'comunigest.new-incident'] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
            $this->assertContains('auth', $middleware, "{$routeName} must require authentication.");
        }
    }

    public function test_portal_dashboard_selects_the_community_portal_for_owners_and_employees(): void
    {
        $view = file_get_contents(resource_path('views/filament/portal/pages/dashboard.blade.php'));

        $this->assertStringContainsString("['owner', 'employee']", $view);
        $this->assertStringContainsString("@livewire('community-portal', ['embedded' => true])", $view);
        $this->assertStringContainsString("@livewire('mobile-portal'", $view);
    }
}
