<?php

namespace Tests\Feature\Community;

use App\Filament\App\Community\Resources\CommunityAppointments\CommunityAppointmentResource;
use App\Filament\App\Community\Resources\CommunityDepartments\CommunityDepartmentResource;
use App\Filament\App\Community\Resources\CommunityDocumentImports\CommunityDocumentImportResource;
use App\Filament\App\Community\Resources\CommunityDocumentTypes\CommunityDocumentTypeResource;
use App\Filament\App\Community\Resources\CommunityOwnerDocuments\CommunityOwnerDocumentResource;
use App\Filament\App\Community\Resources\CommunityTickets\CommunityTicketResource;
use App\Filament\App\Community\Resources\People\PersonResource;
use App\Filament\App\Community\Resources\CommunityProperties\PropertyResource;
use App\Models\CommunityAppointment;
use App\Models\CommunityDepartment;
use App\Models\CommunityDocumentImport;
use App\Models\CommunityDocumentType;
use App\Models\CommunityOwnerDocument;
use App\Models\CommunityTicket;
use App\Models\Person;
use App\Models\Property;
use Tests\TestCase;

class CommunityOperationsNavigationTest extends TestCase
{
    public function test_resources_use_canonical_domain_models(): void
    {
        $this->assertSame(Property::class, PropertyResource::getModel());
        $this->assertSame(Person::class, PersonResource::getModel());
        $this->assertSame(CommunityAppointment::class, CommunityAppointmentResource::getModel());
        $this->assertSame(CommunityTicket::class, CommunityTicketResource::getModel());
        $this->assertSame(CommunityOwnerDocument::class, CommunityOwnerDocumentResource::getModel());
        $this->assertSame(CommunityDocumentType::class, CommunityDocumentTypeResource::getModel());
        $this->assertSame(CommunityDocumentImport::class, CommunityDocumentImportResource::getModel());
        $this->assertSame(CommunityDepartment::class, CommunityDepartmentResource::getModel());
    }

    public function test_operational_views_and_owner_employee_subpages_are_registered(): void
    {
        $router = app('router');
        foreach (['filament.app.resources.community-appointments.index', 'filament.app.resources.community-appointments.calendar', 'filament.app.resources.community-appointments.kanban', 'filament.app.resources.community-owner-documents.index', 'filament.app.resources.community-document-types.index', 'filament.app.resources.community-document-imports.index', 'filament.app.resources.community-departments.index', 'filament.app.resources.community-tickets.index', 'filament.app.resources.community-users.index', 'filament.app.resources.owners.properties', 'filament.app.resources.owners.documents', 'filament.app.resources.owners.appointments', 'filament.app.resources.owners.tickets', 'filament.app.resources.owners.fees', 'filament.app.resources.employees.departments', 'filament.app.resources.employees.shifts', 'filament.app.resources.employees.attendances'] as $route) {
            $this->assertTrue($router->has($route), "Missing route: {$route}");
        }
    }
}
