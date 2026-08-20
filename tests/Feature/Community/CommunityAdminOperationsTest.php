<?php

namespace Tests\Feature\Community;

use App\Filament\App\Community\Resources\Communities\CommunityResource;
use App\Filament\App\Community\Resources\Communities\RelationManagers\IncidentsRelationManager;
use App\Filament\App\Community\Resources\Communities\RelationManagers\PlansRelationManager;
use App\Filament\App\Community\Resources\Communities\RelationManagers\TasksRelationManager;
use App\Filament\App\Community\Resources\Communities\RelationManagers\WorkOrdersRelationManager;
use App\Models\Community;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Tests\TestCase;

class CommunityAdminOperationsTest extends TestCase
{
    public function test_community_exposes_all_operational_relationships_in_filament(): void
    {
        $relations = CommunityResource::getRelations();

        $this->assertContains(PlansRelationManager::class, $relations);
        $this->assertContains(WorkOrdersRelationManager::class, $relations);
        $this->assertContains(TasksRelationManager::class, $relations);
        $this->assertContains(IncidentsRelationManager::class, $relations);
        $this->assertInstanceOf(HasMany::class, (new Community)->plans());
        $this->assertInstanceOf(HasMany::class, (new Community)->workOrders());
        $this->assertInstanceOf(HasManyThrough::class, (new Community)->workOrderTasks());
        $this->assertInstanceOf(HasMany::class, (new Community)->incidents());
    }

    public function test_community_calendar_page_is_registered_and_opens_work_orders(): void
    {
        $router = app('router');
        $calendar = file_get_contents(resource_path('views/filament/app/community/communities/calendar.blade.php'));

        $this->assertTrue($router->has('filament.app.resources.communities.calendar'));
        $this->assertStringContainsString("WorkOrderResource::getUrl('view'", $calendar);
        $this->assertStringContainsString('pending_tasks_count', $calendar);
        $this->assertStringContainsString('Órdenes generadas por los planes', $calendar);
    }

    public function test_plan_generation_opens_the_generated_orders(): void
    {
        $resource = file_get_contents(app_path('Filament/App/Community/Resources/CommunityPlans/CommunityPlanResource.php'));
        $viewPage = file_get_contents(app_path('Filament/App/Community/Resources/CommunityPlans/Pages/ViewCommunityPlan.php'));

        $this->assertStringContainsString('Generar y abrir órdenes', $resource);
        $this->assertStringContainsString("getUrl('orders'", $resource);
        $this->assertStringContainsString('Generar y abrir órdenes', $viewPage);
        $this->assertStringContainsString("getUrl('orders'", $viewPage);
    }
}
