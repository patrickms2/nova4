<?php

namespace Tests\Feature\Community;

use App\Actions\Community\RegenerateCommunityPlanWorkOrders;
use App\Actions\Community\ResolveIncident;
use App\Actions\Community\TransitionWorkOrder;
use App\Actions\Community\TransitionWorkOrderTask;
use App\Filament\App\Community\Pages\CommunityDashboard;
use App\Filament\App\Community\Resources\Communities\Pages\ListCommunities;
use App\Filament\App\Community\Resources\Incidents\Pages\ListIncidents;
use App\Filament\App\Community\Resources\WorkOrders\Pages\ListWorkOrders;
use App\Models\Community;
use App\Models\CommunityPlan;
use App\Models\Employee;
use App\Models\Incident;
use App\Models\User;
use App\Models\WorkCatalog;
use App\Models\WorkCategory;
use App\Models\WorkOrder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentCommunityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_existing_comunigest_routes_and_new_filament_routes_coexist(): void
    {
        $this->assertTrue(app('router')->has('comunigest.dashboard'));
        $this->assertTrue(app('router')->has('comunigest.inicio'));
        $this->assertTrue(app('router')->has('comunigest.work-order'));
        $this->assertTrue(app('router')->has('filament.app.pages.community-dashboard'));
        $this->assertTrue(app('router')->has('filament.app.resources.communities.index'));
    }

    public function test_work_order_and_task_transitions_preserve_legacy_behavior(): void
    {
        $user = User::factory()->create();
        $community = Community::create(['code' => 'C-'.uniqid(), 'name' => 'Los Marinos', 'status' => 'active']);
        $order = WorkOrder::create(['community_id' => $community->id, 'code' => 'OT-'.uniqid(), 'work_date' => today(), 'status' => 'pending']);
        $task = $order->tasks()->create(['source_type' => 'EXTRA', 'title' => 'Cortar césped', 'priority' => 'normal', 'status' => 'pending']);

        app(TransitionWorkOrder::class)->handle($order, 'in_progress', $user->id);
        app(TransitionWorkOrderTask::class)->handle($task, 'completed', $user->id);
        app(TransitionWorkOrder::class)->handle($order, 'finished', $user->id);

        $this->assertSame('finished', $order->refresh()->status);
        $this->assertNotNull($order->finished_at);
        $this->assertSame('completed', $task->refresh()->status);
        $this->assertSame('correcto', $task->result);
    }

    public function test_resolving_incident_stamps_actor_and_time(): void
    {
        $user = User::factory()->create();
        $community = Community::create(['code' => 'C-'.uniqid(), 'name' => 'Test', 'status' => 'active']);
        $incident = Incident::create(['community_id' => $community->id, 'title' => 'Fuga', 'priority' => 'urgent', 'status' => 'open']);

        app(ResolveIncident::class)->handle($incident, 'resolved', $user->id, 'Reparada');

        $this->assertSame('resolved', $incident->refresh()->status);
        $this->assertSame($user->id, $incident->resolved_by);
        $this->assertNotNull($incident->resolved_at);
    }

    public function test_active_plan_regenerates_work_orders_from_plan_days(): void
    {
        $community = Community::create(['code' => 'C-'.uniqid(), 'name' => 'Plan Test', 'status' => 'active']);
        $monday = now()->next('Monday')->startOfDay();
        $plan = CommunityPlan::create(['community_id' => $community->id, 'valid_from' => $monday, 'valid_until' => $monday, 'status' => 'active']);
        $item = $plan->items()->create(['title' => 'Piscina', 'sort' => 0, 'active' => true]);
        $item->days()->create(['day_of_week' => 1]);

        app(RegenerateCommunityPlanWorkOrders::class)->handle($plan, null);

        $order = WorkOrder::where('reference', 'PLAN-'.$plan->id)->first();
        $this->assertNotNull($order);
        $this->assertSame('Piscina', $order->tasks()->value('title'));
    }

    public function test_plan_tasks_are_assigned_to_candidates_matching_the_service_type(): void
    {
        $community = Community::create(['code' => 'C-'.uniqid(), 'name' => 'Servicios Test', 'status' => 'active']);
        $category = WorkCategory::create(['name' => 'Jardinería '.uniqid(), 'active' => true]);
        $service = WorkCatalog::create(['work_category_id' => $category->id, 'title' => 'Regar jardín', 'active' => true]);
        $employee = Employee::create(['name' => 'Empleado jardinero', 'employee_code' => 'EMP-'.uniqid(), 'active' => true]);
        $employee->workCategories()->attach($category);

        $monday = now()->next('Monday')->startOfDay();
        $plan = CommunityPlan::create(['community_id' => $community->id, 'name' => 'Plan semanal', 'valid_from' => $monday, 'valid_until' => $monday, 'status' => 'active']);
        $item = $plan->items()->create(['work_catalog_id' => $service->id, 'title' => $service->title, 'sort' => 0, 'active' => true]);
        $item->days()->create(['day_of_week' => 1]);
        $item->candidateEmployees()->attach($employee);

        app(RegenerateCommunityPlanWorkOrders::class)->handle($plan, null, [$item->id => $employee->id]);

        $task = WorkOrder::where('reference', 'PLAN-'.$plan->id)->firstOrFail()->tasks()->firstOrFail();

        $this->assertSame($employee->id, $task->user_id);
        $this->assertTrue($employee->workCategories->contains($category));
        $this->assertTrue($item->candidateEmployees->contains($employee));
    }

    public function test_plan_generation_rejects_an_employee_who_is_not_a_candidate(): void
    {
        $community = Community::create(['code' => 'C-'.uniqid(), 'name' => 'Validación Test', 'status' => 'active']);
        $employee = Employee::create(['name' => 'Empleado no candidato', 'employee_code' => 'EMP-'.uniqid(), 'active' => true]);
        $monday = now()->next('Monday')->startOfDay();
        $plan = CommunityPlan::create(['community_id' => $community->id, 'name' => 'Plan validado', 'valid_from' => $monday, 'valid_until' => $monday, 'status' => 'active']);
        $item = $plan->items()->create(['title' => 'Tarea protegida', 'sort' => 0, 'active' => true]);
        $item->days()->create(['day_of_week' => 1]);

        $this->expectException(ValidationException::class);

        app(RegenerateCommunityPlanWorkOrders::class)->handle($plan, null, [$item->id => $employee->id]);
    }

    public function test_filament_community_pages_boot(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Livewire::test(CommunityDashboard::class)->assertSuccessful();
        Livewire::test(ListCommunities::class)->assertSuccessful();
        Livewire::test(ListWorkOrders::class)->assertSuccessful();
        Livewire::test(ListIncidents::class)->assertSuccessful();
    }
}
