<?php

namespace Tests\Feature\Community;

use App\Actions\Community\ConvertTicketToWorkOrder;
use App\Livewire\CommunityPortal;
use App\Models\CommunityTicket;
use App\Models\WorkOrder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UnifiedCommunityAppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('people', function (Blueprint $table): void {
            $table->id();
            $table->string('display_name')->nullable();
            $table->string('phone')->nullable();
        });
        Schema::create('community_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('community_id');
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->dateTime('due_at')->nullable();
            $table->timestamps();
        });
        Schema::create('work_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('community_id');
            $table->string('code')->unique();
            $table->date('work_date');
            $table->string('status');
            $table->string('requester_name')->nullable();
            $table->string('requester_phone')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('work_order_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('work_order_id');
            $table->string('source_type')->nullable();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->string('priority')->nullable();
            $table->string('status');
            $table->string('requester_name')->nullable();
            $table->string('requester_phone')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_comunigest_inicio_is_the_single_role_aware_community_app(): void
    {
        $route = Route::getRoutes()->getByName('comunigest.inicio');

        $this->assertSame(CommunityPortal::class, $route?->getActionName());
        $this->assertContains('auth', $route?->gatherMiddleware() ?? []);
    }

    public function test_ticket_conversion_creates_one_work_order_and_one_task(): void
    {
        $ticket = CommunityTicket::create(['community_id' => 7, 'title' => 'Fuga en garaje', 'description' => 'Revisar tubería', 'priority' => 'high', 'status' => 'open']);

        $first = app(ConvertTicketToWorkOrder::class)->handle($ticket, 12);
        $second = app(ConvertTicketToWorkOrder::class)->handle($ticket->refresh(), 12);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('in_progress', $ticket->refresh()->status);
        $this->assertSame(1, WorkOrder::count());
        $this->assertSame(1, $first->tasks()->count());
        $this->assertSame('COMMUNITY_TICKET', $first->tasks()->value('source_type'));
    }

    public function test_unified_view_contains_owner_and_employee_navigation(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));

        $this->assertStringContainsString('PROPIEDADES', $view);
        $this->assertStringContainsString('CUOTAS', $view);
        $this->assertStringContainsString('PLANES', $view);
        $this->assertStringContainsString('ASISTENCIA', $view);
        $this->assertStringContainsString('convertTicketToWorkOrder', $view);
    }

    public function test_unified_view_exposes_portal_interactions_and_visual_language(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));

        $this->assertStringContainsString('community-glass', $view);
        $this->assertStringContainsString('showSpotlight', $view);
        $this->assertStringContainsString('showFilters', $view);
        $this->assertStringContainsString('showTicketModal', $view);
        $this->assertStringContainsString('wire:model.live.debounce.300ms="search"', $view);
        $this->assertStringContainsString('x-on:keydown.meta.k.window.prevent', $view);
        $this->assertStringContainsString('community-ticket-created', $view);
    }

    public function test_employee_portal_exposes_operational_creation_flows(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));
        $component = file_get_contents(app_path('Livewire/CommunityPortal.php'));

        $this->assertStringContainsString('NUEVA CITA', $view);
        $this->assertStringContainsString('Foto y descripción', $view);
        $this->assertStringContainsString('Ticket de gasto', $view);
        $this->assertStringContainsString('wire:model="entryFile"', $view);
        $this->assertStringContainsString('createEmployeeAppointment', $component);
        $this->assertStringContainsString('createEmployeeDocument', $component);
        $this->assertStringContainsString('createEmployeeIncident', $component);
        $this->assertStringContainsString('createEmployeeExpenseTicket', $component);
        $this->assertStringContainsString("whereIn('status', ['pending', 'in_progress'])", $component);
    }

    public function test_employee_operations_allow_optional_community_and_receipt_ocr(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));
        $component = file_get_contents(app_path('Livewire/CommunityPortal.php'));
        $camera = file_get_contents(resource_path('views/components/community-camera-capture.blade.php'));
        $migration = file_get_contents(database_path('migrations/2026_08_15_103208_make_employee_portal_community_context_optional.php'));

        $this->assertStringContainsString("'employeeCommunityId' => ['nullable', 'integer']", $component);
        $this->assertStringContainsString("'attendanceCommunityIds' => ['array']", $component);
        $this->assertStringContainsString("'entryFile' => ['nullable', 'image'", $component);
        $this->assertStringContainsString('ExtractReceiptData $receiptDataExtractor', $component);
        $this->assertStringContainsString('public function updatedEntryFile', $component);
        $this->assertStringContainsString("employeeEntryType !== 'expense'", $component);
        $this->assertStringContainsString("x-effect=\"\$wire.set('employeeEntryType'", $view);
        $this->assertStringContainsString('selectExpenseInputMode', $component);
        $this->assertStringContainsString("['photo', 'ocr', 'manual']", $component);
        $this->assertStringContainsString('FOTO', $view);
        $this->assertStringContainsString('OCR', $view);
        $this->assertStringContainsString('MANUAL', $view);
        $this->assertStringContainsString('Reconociendo recibo…', $view);
        $this->assertStringContainsString('Recibo reconocido', $view);
        $this->assertStringContainsString("\$section === 'plans' ? 'work'", $component);
        $this->assertStringContainsString('Sin comunidad específica', $view);
        $this->assertStringContainsString('Comunidades visitadas <small', $view);
        $this->assertStringContainsString("this.\$wire.\$upload('entryFile'", $camera);
        $this->assertStringContainsString("['community_appointments', 'community_employee_documents', 'incidents', 'photos', 'community_tickets']", $migration);
    }

    public function test_section_lists_replace_home_kpis_and_expose_toolbar_actions(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));
        $component = file_get_contents(app_path('Livewire/CommunityPortal.php'));

        $this->assertStringContainsString("@if (\$section === 'home')", $view);
        $this->assertStringContainsString('Listado', $view);
        $this->assertStringContainsString('Volver al inicio', $view);
        $this->assertStringContainsString('Mostrar filtros', $view);
        $this->assertStringContainsString('Añadir registro', $view);
        $this->assertStringContainsString('selectOnlyEmployeeCommunity', $component);
        $this->assertStringContainsString("dispatch('community-section-changed')", $component);
    }

    public function test_owner_and_employee_share_creation_flows_and_role_aware_onboarding(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));
        $onboarding = file_get_contents(resource_path('views/livewire/community-portal-onboarding.blade.php'));
        $component = file_get_contents(app_path('Livewire/CommunityPortal.php'));

        $this->assertStringContainsString('createOwnerAppointment', $component);
        $this->assertStringContainsString('createOwnerDocument', $component);
        $this->assertStringContainsString('createOwnerIncident', $component);
        $this->assertStringContainsString('createEmployeeTicket', $component);
        $this->assertStringContainsString("['appointments', 'documents', 'tickets', 'incidents', 'expenses']", $view);
        $this->assertStringContainsString('community-portal-onboarding', $view);
        $this->assertStringContainsString("role === 'employee'", $onboarding);
        $this->assertStringContainsString('nova-community-${this.role}-onboarding-v1', $onboarding);
        $this->assertStringContainsString('Tus propiedades y cuotas', $onboarding);
        $this->assertStringContainsString('Tu trabajo diario', $onboarding);
    }

    public function test_spotlight_exposes_creation_session_and_detail_actions(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));
        $component = file_get_contents(app_path('Livewire/CommunityPortal.php'));

        $this->assertStringContainsString('Solicitar cita', $view);
        $this->assertStringContainsString('Nueva solicitud', $view);
        $this->assertStringContainsString('Subir documentación', $view);
        $this->assertStringContainsString('Foto y descripción', $view);
        $this->assertStringContainsString('REGISTRAR SESIÓN', $view);
        $this->assertStringContainsString('$wire.logout()', $view);
        $this->assertStringContainsString('x-community-record-row', $view);
        $this->assertStringContainsString('x-community-appointment-row', $view);
        $this->assertStringContainsString("openDetail('fee'", $view);
        $this->assertStringContainsString('Abrir archivo', $view);
        $this->assertStringContainsString('public function logout(): void', $component);
    }

    public function test_portal_appointments_only_request_community_date_time_and_reason(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));
        $component = file_get_contents(app_path('Livewire/CommunityPortal.php'));

        $this->assertStringContainsString('Hora disponible', $view);
        $this->assertStringContainsString('Motivo de la cita', $view);
        $this->assertStringContainsString('wire:model.live="appointmentDate"', $view);
        $this->assertStringContainsString('wire:model.live="ownerCommunityId"', $view);
        $this->assertStringContainsString('CommunityAppointmentAvailability', $component);
        $this->assertStringContainsString("'starts_at' => \$validated['appointmentDate'].' '.\$validated['appointmentTime']", $component);
    }

    public function test_community_lists_and_details_reuse_the_portal_visual_language(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));
        $appointmentRow = file_get_contents(resource_path('views/components/community-appointment-row.blade.php'));
        $recordRow = file_get_contents(resource_path('views/components/community-record-row.blade.php'));

        $this->assertStringContainsString('community-portal-row', $appointmentRow);
        $this->assertStringContainsString('border-l-blue-500', $appointmentRow);
        $this->assertStringContainsString('-rotate-90', $appointmentRow);
        $this->assertStringContainsString('community-portal-row', $recordRow);
        $this->assertStringContainsString('community-detail-shell', $view);
        $this->assertStringContainsString('community-detail-surface', $view);
        $this->assertStringContainsString('community-form-shell', $view);
        $this->assertStringContainsString('backdrop-filter: blur(28px)', $view);
    }

    public function test_owner_and_employee_incidents_use_integrated_camera_capture(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));
        $camera = file_get_contents(resource_path('views/components/community-camera-capture.blade.php'));

        $this->assertSame(2, substr_count($view, '<x-community-camera-capture />'));
        $this->assertStringContainsString("employeeEntryModal === 'incident'", $view);
        $this->assertStringContainsString("ownerEntryModal === 'incident'", $view);
        $this->assertStringContainsString('navigator.mediaDevices.getUserMedia', $camera);
        $this->assertStringContainsString("facingMode: { ideal: 'environment' }", $camera);
        $this->assertStringContainsString("this.\$wire.\$upload('entryFile'", $camera);
        $this->assertStringNotContainsString("this.\$wire.upload('entryFile'", $camera);
        $this->assertStringContainsString('canvas.toBlob', $camera);
        $this->assertStringContainsString('Repetir foto', $camera);
        $this->assertStringContainsString('community-camera-reset', $camera);
    }
}
