<?php

namespace Tests\Feature\Community;

use App\Filament\App\Community\Resources\Employees\EmployeeResource;
use App\Filament\App\Community\Resources\Owners\OwnerResource;
use App\Models\Community;
use App\Models\CommunityAppointment;
use App\Models\CommunityAttendance;
use App\Models\CommunityDepartment;
use App\Models\CommunityFee;
use App\Models\CommunityOwnerDocument;
use App\Models\CommunityShift;
use App\Models\CommunityTicket;
use App\Models\Employee;
use App\Models\Person;
use App\Models\Property;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class CommunityOwnerOperationsTest extends TestCase
{
    public function test_community_owner_filament_uses_the_canonical_person_and_property_models(): void
    {
        $this->assertSame(Person::class, OwnerResource::getModel());
        $this->assertInstanceOf(BelongsToMany::class, (new Person)->communities());
        $this->assertInstanceOf(BelongsToMany::class, (new Person)->properties());
        $this->assertInstanceOf(HasMany::class, (new Community)->properties());
        $this->assertInstanceOf(BelongsTo::class, (new Property)->community());
    }

    public function test_owner_operations_are_related_to_canonical_people_and_properties(): void
    {
        foreach ([
            new CommunityOwnerDocument,
            new CommunityAppointment,
            new CommunityTicket,
            new CommunityFee,
        ] as $operation) {
            $this->assertInstanceOf(BelongsTo::class, $operation->person());
            $this->assertInstanceOf(BelongsTo::class, $operation->property());
            $this->assertInstanceOf(BelongsTo::class, $operation->community());
        }
    }

    public function test_employee_operations_extend_the_existing_employee_model(): void
    {
        $this->assertSame(Employee::class, EmployeeResource::getModel());
        $this->assertInstanceOf(BelongsToMany::class, (new Employee)->communityDepartments());
        $this->assertInstanceOf(HasMany::class, (new Employee)->communityShifts());
        $this->assertInstanceOf(HasMany::class, (new Employee)->communityAttendances());
        $this->assertInstanceOf(BelongsTo::class, (new CommunityShift)->employee());
        $this->assertInstanceOf(BelongsTo::class, (new CommunityAttendance)->employee());
        $this->assertInstanceOf(HasMany::class, (new CommunityDepartment)->shifts());
    }

    public function test_owner_and_employee_pages_are_registered_in_the_existing_app_panel(): void
    {
        $router = app('router');

        $this->assertTrue($router->has('filament.app.resources.owners.index'));
        $this->assertTrue($router->has('filament.app.resources.owners.view'));
        $this->assertTrue($router->has('filament.app.resources.employees.index'));
        $this->assertTrue($router->has('filament.app.resources.employees.view'));
        $this->assertTrue($router->has('comunigest.dashboard'));
    }
}
