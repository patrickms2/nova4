<?php

namespace App\Livewire;

use App\Actions\Community\ConvertTicketToWorkOrder;
use App\Actions\Community\TranscribeAttendanceAudio;
use App\Actions\ExtractReceiptData;
use App\Models\Community;
use App\Models\CommunityAppointment;
use App\Models\CommunityAttendance;
use App\Models\CommunityEmployeeDocument;
use App\Models\CommunityOwnerDocument;
use App\Models\CommunityPlan;
use App\Models\CommunityShift;
use App\Models\CommunityTicket;
use App\Models\Employee;
use App\Models\Incident;
use App\Models\Photo;
use App\Models\WorkCatalog;
use App\Models\WorkCategory;
use App\Models\WorkOrder;
use App\Support\CommunityAppointmentAvailability;
use App\Support\CommunityPortalContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

class CommunityPortal extends Component
{
    use WithFileUploads;

    public string $section = 'home';

    public bool $embedded = false;

    public string $ticketTitle = '';

    public string $ticketDescription = '';

    public ?int $ticketPropertyId = null;

    public ?string $message = null;

    public string $search = '';

    public string $statusFilter = '';

    public string $plansFilter = '';

    public string $sourceFilter = '';

    public ?int $employeeCommunityId = null;

    public string $entryTitle = '';

    public string $entryDescription = '';

    public string $appointmentStartsAt = '';

    public string $appointmentDate = '';

    public string $appointmentTime = '';

    public ?int $ownerCommunityId = null;

    public ?int $incidentWorkOrderId = null;

    public string $incidentPriority = 'normal';

    public ?int $incidentWorkCategoryId = null;

    public ?int $incidentWorkCatalogId = null;

    public ?int $planId = null;

    public ?int $communityFilter = null;

    public string $expenseAmount = '';

    public string $employeeEntryType = '';

    public string $expenseInputMode = 'ocr';

    /** @var array<string, mixed> */
    public array $receiptOcrData = [];

    public ?TemporaryUploadedFile $entryFile = null;

    public ?TemporaryUploadedFile $attendanceAudio = null;

    /** @var array<int, int|string> */
    public array $attendanceCommunityIds = [];

    public ?float $attendanceLatitude = null;

    public ?float $attendanceLongitude = null;

    public ?int $attendanceAccuracy = null;

    public ?string $detailType = null;

    public ?int $detailId = null;

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->selectOnlyEmployeeCommunity();
        $this->selectOnlyOwnerCommunity();
        $requestedSection = request()->query('section');
        if (is_string($requestedSection)) {
            $this->show($requestedSection);
        }
    }

    public function show(string $section): void
    {
        $allowed = CommunityPortalContext::portalType() === 'employee'
            ? ['home', 'plans', 'communities', 'work', 'incidents', 'shifts', 'attendance', 'appointments', 'documents', 'tickets', 'expenses']
            : ['home', 'properties', 'documents', 'appointments', 'tickets', 'incidents', 'fees'];

        if (in_array($section, $allowed, true)) {
            $this->section = $section;
            $this->reset('search', 'statusFilter', 'plansFilter', 'sourceFilter', 'communityFilter', 'planId');
            $this->dispatch('community-section-changed');
        }

    }

    public function clearFilters(): void
    {
        $this->reset('search', 'statusFilter', 'plansFilter', 'sourceFilter', 'communityFilter', 'planId');
    }

    public function showCommunityPlans(int $communityId): void
    {
        abort_unless(CommunityPortalContext::isEmployee(), 403);
        abort_unless(CommunityPortalContext::employeeCommunityIds()->contains($communityId), 403);

        $this->reset('search', 'statusFilter', 'plansFilter', 'sourceFilter');
        $this->communityFilter = $communityId;
        $this->planId = null;
        $this->section = 'plans';
        $this->dispatch('community-section-changed');
    }

    public function showPlanOrders(int $planId): void
    {
        abort_unless(CommunityPortalContext::isEmployee(), 403);
        abort_unless(
            CommunityPlan::whereIn('community_id', CommunityPortalContext::employeeCommunityIds())->whereKey($planId)->exists(),
            403
        );

        $this->reset('search', 'statusFilter', 'plansFilter', 'sourceFilter');
        $this->communityFilter = null;
        $this->planId = $planId;
        $this->section = 'work';
        $this->dispatch('community-section-changed');
    }

    public function openDetail(string $type, int $id): void
    {
        abort_unless(in_array($type, ['wokOrders', 'plans', 'plan', 'document', 'appointment', 'ticket', 'fee', 'incident', 'community'], true), 404);
        abort_unless($id > 0, 404);

        $this->detailType = $type;
        $this->detailId = $id;
    }

    public function closeDetail(): void
    {
        $this->reset('detailType', 'detailId');
    }

    public function updatedIncidentWorkCategoryId(): void
    {
        $this->incidentWorkCatalogId = null;
    }

    public function updatedEntryFile(ExtractReceiptData $receiptDataExtractor): void
    {
        if ($this->employeeEntryType !== 'expense' || $this->expenseInputMode === 'manual' || ! $this->entryFile) {
            return;
        }

        $this->validateOnly('entryFile', ['entryFile' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);

        try {
            $this->receiptOcrData = $receiptDataExtractor->handle($this->entryFile->getRealPath(), $this->entryFile->getMimeType());
            $this->entryTitle = trim((string) ($this->receiptOcrData['concepto'] ?? '')) ?: $this->entryTitle;
            $this->entryDescription = trim(implode(' · ', array_filter([
                $this->receiptOcrData['empresa'] ?? null,
                $this->receiptOcrData['concepto'] ?? null,
                $this->receiptOcrData['fecha'] ?? null,
            ]))) ?: $this->entryDescription;

            if (! empty($this->receiptOcrData['total'])) {
                $this->expenseAmount = (string) $this->receiptOcrData['total'];
            }

            $this->message = 'Recibo reconocido. Revisa los datos antes de enviarlo.';
        } catch (Throwable $exception) {
            report($exception);
            $this->receiptOcrData = [];
            $this->message = 'No se pudieron reconocer los datos del recibo. Puedes completarlos manualmente.';
        }
    }

    public function selectExpenseInputMode(string $mode): void
    {
        abort_unless(in_array($mode, ['photo', 'ocr', 'manual'], true), 422);

        $this->expenseInputMode = $mode;
        $this->reset('entryFile', 'receiptOcrData', 'entryTitle', 'entryDescription', 'expenseAmount');
        $this->resetValidation(['entryFile', 'entryTitle', 'entryDescription', 'expenseAmount']);
        $this->dispatch('community-camera-reset');
    }

    public function logout(): void
    {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirect(route('comunigest.login'), navigate: true);
    }

    public function render(): View
    {
        $view = CommunityPortalContext::portalType() === 'employee'
            ? $this->renderEmployee()
            : $this->renderOwner();

        return $this->embedded ? $view : $view->layout('layouts.mobile');
    }

    public function createTicket(): void
    {
        abort_unless(CommunityPortalContext::isOwner(), 403);
        $person = CommunityPortalContext::person();
        abort_unless($person, 403);

        $validated = $this->validate([
            'ticketTitle' => ['required', 'string', 'max:255'],
            'ticketDescription' => ['required', 'string', 'max:3000'],
            'ticketPropertyId' => ['required', 'integer'],
        ]);
        $property = $person->properties()->whereKey($validated['ticketPropertyId'])->firstOrFail();
        abort_unless($property->community_id, 422, 'La propiedad no está vinculada a una comunidad.');

        CommunityTicket::create([
            'community_id' => $property->community_id,
            'person_id' => $person->id,
            'property_id' => $property->id,
            'title' => $validated['ticketTitle'],
            'description' => $validated['ticketDescription'],
            'priority' => 'normal',
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        $this->reset('ticketTitle', 'ticketDescription', 'ticketPropertyId');
        $this->message = 'Ticket enviado a la comunidad.';
        $this->dispatch('community-ticket-created');
    }

    public function createOwnerAppointment(): void
    {
        [$person, $community] = $this->authorizedOwnerCommunity();
        $validated = $this->validate([
            'entryTitle' => ['required', 'string', 'max:255'],
            'appointmentDate' => ['required', 'date', 'after_or_equal:today'],
            'appointmentTime' => ['required', 'date_format:H:i'],
        ]);
        abort_unless(app(CommunityAppointmentAvailability::class)->isAvailable($community->id, $validated['appointmentDate'], $validated['appointmentTime']), 422, 'La hora seleccionada ya no está disponible.');

        CommunityAppointment::create([
            'community_id' => $community->id,
            'person_id' => $person->id,
            'title' => $validated['entryTitle'],
            'starts_at' => $validated['appointmentDate'].' '.$validated['appointmentTime'],
            'status' => 'scheduled',
            'created_by' => auth()->id(),
        ]);

        $this->completeOwnerEntry('Cita solicitada correctamente.', 'appointments');
    }

    public function createOwnerDocument(): void
    {
        [$person, $property] = $this->authorizedOwnerProperty();
        $validated = $this->validate([
            'entryTitle' => ['required', 'string', 'max:255'],
            'entryDescription' => ['nullable', 'string', 'max:3000'],
            'entryFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $path = $this->entryFile->store('comunigest/owner-documents', 'public');

        CommunityOwnerDocument::create([
            'community_id' => $property->community_id,
            'person_id' => $person->id,
            'property_id' => $property->id,
            'type' => 'owner_upload',
            'title' => $validated['entryTitle'],
            'path' => $path,
            'status' => 'active',
            'metadata' => ['description' => $validated['entryDescription'], 'filename' => $this->entryFile->getClientOriginalName()],
            'uploaded_by' => auth()->id(),
        ]);

        $this->completeOwnerEntry('Documento enviado correctamente.', 'documents');
    }

    public function createOwnerIncident(): void
    {
        [$person, $property] = $this->authorizedOwnerProperty();
        $validated = $this->validate([
            'entryTitle' => ['required', 'string', 'max:255'],
            'entryDescription' => ['required', 'string', 'max:3000'],
            'incidentPriority' => ['required', 'in:low,normal,high,urgent'],
            'incidentWorkCategoryId' => ['required', 'integer', Rule::exists('work_categories', 'id')->where('active', true)],
            'incidentWorkCatalogId' => ['nullable', 'integer', Rule::exists('work_catalog', 'id')->where(fn ($query) => $query->where('active', true)->where('work_category_id', $this->incidentWorkCategoryId))],
            'entryFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $incident = Incident::create([
            'community_id' => $property->community_id,
            'property_id' => $property->id,
            'person_id' => $person->id,
            'user_id' => $person->id,
            'title' => $validated['entryTitle'],
            'description' => $validated['entryDescription'],
            'priority' => $validated['incidentPriority'],
            'work_category_id' => $validated['incidentWorkCategoryId'],
            'work_catalog_id' => $validated['incidentWorkCatalogId'] ?? null,
            'status' => 'open',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
 if ($this->entryFile) {
            $path = $this->entryFile->store('comunigest/photos', 'public');

            Photo::create([
                'community_id' => $property->community_id,
                'incident_id' => $incident->id,
                'path' => $path,
                'filename' => $this->entryFile->getClientOriginalName(),
                'mime_type' => $this->entryFile->getMimeType(),
                'size' => $this->entryFile->getSize(),
                'taken_at' => now(),
                'uploaded_by' => auth()->id(),
                'active' => true,
            ]);
        }

        $this->completeOwnerEntry('Incidencia enviada correctamente.', 'incidents');
    }

    public function convertTicketToWorkOrder(int $ticketId): void
    {
        abort_unless(CommunityPortalContext::isEmployee(), 403);
        $ticket = CommunityTicket::findOrFail($ticketId);
        abort_unless(CommunityPortalContext::employeeCommunityIds()->contains((int) $ticket->community_id), 403);
        abort_if($ticket->type === 'expense', 422, 'Los gastos no se convierten en órdenes de trabajo.');

        app(ConvertTicketToWorkOrder::class)->handle($ticket, auth()->id());
        $this->message = 'Ticket convertido en orden de trabajo.';
        $this->section = 'work';
    }

    public function registerAttendance(TranscribeAttendanceAudio $transcriber): void
    {
        $employee = $this->authorizedEmployee();
        $validated = $this->validate([
            'attendanceCommunityIds' => ['array'],
            'attendanceCommunityIds.*' => ['integer', 'distinct'],
            'attendanceLatitude' => ['required', 'numeric', 'between:-90,90'],
            'attendanceLongitude' => ['required', 'numeric', 'between:-180,180'],
            'attendanceAccuracy' => ['nullable', 'integer', 'min:0'],
        ]);
        $communityIds = collect($validated['attendanceCommunityIds'])->map(fn ($id): int => (int) $id)->unique()->values();
        $communityIds->each(fn (int $communityId) => $this->authorizeEmployeeCommunity($communityId));
        $primaryCommunityId = $communityIds->first();

        $attendance = CommunityAttendance::firstOrNew(['employee_id' => $employee->id, 'attendance_date' => today()->toDateString()]);
        $department = $employee->communityDepartments()
            ->where(function ($query) use ($communityIds): void {
                $query->whereNull('community_departments.community_id');

                if ($communityIds->isNotEmpty()) {
                    $query->orWhereIn('community_departments.community_id', $communityIds);
                }
            })
            ->first();
        $attendance->fill([
            'community_id' => $attendance->exists ? $attendance->community_id : $primaryCommunityId,
            'community_department_id' => $attendance->exists ? $attendance->community_department_id : $department?->id,
            'type' => 'presence',
            'status' => 'recorded',
            'recorded_by' => auth()->id(),
        ]);

        if (! $attendance->checked_in_at) {
            $attendance->fill([
                'checked_in_at' => now(),
                'check_in_latitude' => $validated['attendanceLatitude'],
                'check_in_longitude' => $validated['attendanceLongitude'],
                'check_in_accuracy' => $validated['attendanceAccuracy'],
            ]);
            $this->message = 'Entrada registrada.';
        } elseif (! $attendance->checked_out_at) {
            $audio = $this->validate([
                'attendanceAudio' => ['required', 'file', 'mimetypes:audio/*,video/webm,video/mp4', 'max:25600'],
            ])['attendanceAudio'];
            $path = $audio->store('comunigest/attendance-audio', 'local');
            $attendance->fill([
                'checked_out_at' => now(),
                'check_out_latitude' => $validated['attendanceLatitude'],
                'check_out_longitude' => $validated['attendanceLongitude'],
                'check_out_accuracy' => $validated['attendanceAccuracy'],
                'closing_audio_path' => $path,
                'closing_audio_mime_type' => $audio->getMimeType(),
                'transcription_status' => 'processing',
                'transcription_error' => null,
            ]);
            $attendance->save();

            try {
                $attendance->update([
                    'notes' => $transcriber->handle($path),
                    'transcription_status' => 'completed',
                ]);
                $this->message = 'Salida registrada y nota de audio transcrita.';
            } catch (Throwable $exception) {
                Log::warning('Community attendance audio transcription failed.', [
                    'attendance_id' => $attendance->id,
                    'exception' => $exception::class,
                ]);
                $attendance->update([
                    'transcription_status' => 'failed',
                    'transcription_error' => 'No se pudo transcribir automáticamente.',
                ]);
                $this->message = 'Salida registrada. El audio se ha guardado, pero la transcripción está pendiente.';
            }
        } else {
            $this->message = 'La jornada de hoy ya está cerrada.';
        }

        $attendance->save();
        $attendance->communities()->sync($communityIds);
        $this->reset('attendanceAudio', 'attendanceLatitude', 'attendanceLongitude', 'attendanceAccuracy');
        $this->attendanceCommunityIds = $communityIds->all();
        $this->dispatch('community-attendance-recorded');
    }

    public function createEmployeeAppointment(): void
    {
        $this->authorizedEmployee();
        $validated = $this->validate([
            'employeeCommunityId' => ['nullable', 'integer'],
            'entryTitle' => ['required', 'string', 'max:255'],
            'appointmentDate' => ['required', 'date', 'after_or_equal:today'],
            'appointmentTime' => ['required', 'date_format:H:i'],
        ]);
        $communityId = isset($validated['employeeCommunityId']) ? (int) $validated['employeeCommunityId'] : null;

        if ($communityId) {
            $this->authorizeEmployeeCommunity($communityId);
        }

        abort_unless(app(CommunityAppointmentAvailability::class)->isAvailable($communityId, $validated['appointmentDate'], $validated['appointmentTime']), 422, 'La hora seleccionada ya no está disponible.');

        CommunityAppointment::create([
            'community_id' => $communityId,
            'title' => $validated['entryTitle'],
            'starts_at' => $validated['appointmentDate'].' '.$validated['appointmentTime'],
            'status' => 'scheduled',
            'created_by' => auth()->id(),
        ]);

        $this->completeEmployeeEntry('Cita creada correctamente.', 'appointments');
    }

    public function createEmployeeDocument(): void
    {
        $employee = $this->authorizedEmployee();
        $validated = $this->validate([
            'employeeCommunityId' => ['nullable', 'integer'],
            'entryTitle' => ['required', 'string', 'max:255'],
            'entryDescription' => ['nullable', 'string', 'max:3000'],
            'entryFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $communityId = isset($validated['employeeCommunityId']) ? (int) $validated['employeeCommunityId'] : null;

        if ($communityId) {
            $this->authorizeEmployeeCommunity($communityId);
        }
        $path = $this->entryFile->store('comunigest/employee-documents', 'public');

        CommunityEmployeeDocument::create([
            'community_id' => $communityId,
            'employee_id' => $employee->id,
            'title' => $validated['entryTitle'],
            'description' => $validated['entryDescription'],
            'path' => $path,
            'filename' => $this->entryFile->getClientOriginalName(),
            'mime_type' => $this->entryFile->getMimeType(),
            'size' => $this->entryFile->getSize(),
            'status' => 'active',
            'uploaded_by' => auth()->id(),
        ]);

        $this->completeEmployeeEntry('Documento enviado correctamente.', 'documents');
    }

    public function createEmployeeIncident(): void
    {
        $employee = $this->authorizedEmployee();
        $validated = $this->validate([
            'employeeCommunityId' => ['nullable', 'integer'],
            'incidentWorkOrderId' => ['nullable', 'integer', 'exists:work_orders,id'],
            'entryTitle' => ['required', 'string', 'max:255'],
            'entryDescription' => ['required', 'string', 'max:3000'],
            'incidentPriority' => ['required', 'in:low,normal,high,urgent'],
            'incidentWorkCategoryId' => ['required', 'integer', Rule::exists('work_categories', 'id')->where('active', true)],
            'incidentWorkCatalogId' => ['nullable', 'integer', Rule::exists('work_catalog', 'id')->where(fn ($query) => $query->where('active', true)->where('work_category_id', $this->incidentWorkCategoryId))],
            'entryFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $communityId = isset($validated['employeeCommunityId']) ? (int) $validated['employeeCommunityId'] : null;

        $workOrder = isset($validated['incidentWorkOrderId'])
            ? WorkOrder::query()->whereKey($validated['incidentWorkOrderId'])->firstOrFail()
            : null;

        if ($workOrder) {
            $this->authorizeEmployeeCommunity((int) $workOrder->community_id);
            abort_if($communityId && $communityId !== (int) $workOrder->community_id, 422, 'La orden no pertenece a la comunidad seleccionada.');
            $communityId = (int) $workOrder->community_id;
        } elseif ($communityId) {
            $this->authorizeEmployeeCommunity($communityId);
        }
        $incident = Incident::create([
            'community_id' => $communityId,
            'work_order_id' => $workOrder?->id,
            'user_id' => $employee->id,
            'title' => $validated['entryTitle'],
            'description' => $validated['entryDescription'],
            'priority' => $validated['incidentPriority'],
            'work_category_id' => $validated['incidentWorkCategoryId'],
            'work_catalog_id' => $validated['incidentWorkCatalogId'] ?? null,
            'status' => 'open',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
        if ($this->entryFile) {
            $path = $this->entryFile->store('comunigest/photos', 'public');

            Photo::create([
                'community_id' => $communityId,
                'work_order_id' => $workOrder?->id,
                'incident_id' => $incident->id,
                'path' => $path,
                'filename' => $this->entryFile->getClientOriginalName(),
                'mime_type' => $this->entryFile->getMimeType(),
                'size' => $this->entryFile->getSize(),
                'taken_at' => now(),
                'uploaded_by' => auth()->id(),
                'active' => true,
            ]);
        }

        $this->completeEmployeeEntry('Incidencia registrada correctamente.', 'incidents');
    }

    public function createEmployeeExpenseTicket(ExtractReceiptData $receiptDataExtractor): void
    {
        $this->authorizedEmployee();
        $manualEntry = $this->expenseInputMode === 'manual';
        $validated = $this->validate([
            'employeeCommunityId' => ['nullable', 'integer'],
            'expenseInputMode' => ['required', 'in:photo,ocr,manual'],
            'entryTitle' => [$manualEntry ? 'required' : 'nullable', 'string', 'max:255'],
            'entryDescription' => ['nullable', 'string', 'max:3000'],
            'expenseAmount' => [$manualEntry ? 'required' : 'nullable', 'numeric', 'min:0.01', 'max:999999999.99'],
            'entryFile' => [$manualEntry ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $communityId = isset($validated['employeeCommunityId']) ? (int) $validated['employeeCommunityId'] : null;

        if ($communityId) {
            $this->authorizeEmployeeCommunity($communityId);
        }

        $ocr = $this->receiptOcrData;

        if (! $manualEntry && $ocr === []) {
            try {
                $ocr = $receiptDataExtractor->handle($this->entryFile->getRealPath(), $this->entryFile->getMimeType());
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $title = trim($validated['entryTitle'] ?? '') ?: trim((string) ($ocr['concepto'] ?? '')) ?: trim((string) ($ocr['empresa'] ?? '')) ?: 'Gasto con justificante';
        $description = trim($validated['entryDescription'] ?? '') ?: trim(implode(' · ', array_filter([$ocr['empresa'] ?? null, $ocr['concepto'] ?? null, $ocr['fecha'] ?? null])));
        $amount = $validated['expenseAmount'] ?? $ocr['total'] ?? null;

        if (! $amount || (float) $amount <= 0) {
            throw ValidationException::withMessages(['expenseAmount' => 'No se ha podido reconocer el importe. Indícalo manualmente.']);
        }

        $path = $this->entryFile?->store('comunigest/expense-tickets', 'public');

        CommunityTicket::create([
            'community_id' => $communityId,
            'title' => $title,
            'description' => $description ?: 'Justificante reconocido automáticamente.',
            'type' => 'expense',
            'amount' => $amount,
            'attachment_path' => $path,
            'priority' => 'normal',
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        $this->completeEmployeeEntry('Gasto enviado para revisión.', 'expenses');
    }

    public function createEmployeeTicket(): void
    {
        $this->authorizedEmployee();
        $validated = $this->validate([
            'employeeCommunityId' => ['nullable', 'integer'],
            'entryTitle' => ['required', 'string', 'max:255'],
            'entryDescription' => ['required', 'string', 'max:3000'],
            'entryFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $communityId = isset($validated['employeeCommunityId']) ? (int) $validated['employeeCommunityId'] : null;

        if ($communityId) {
            $this->authorizeEmployeeCommunity($communityId);
        }

        CommunityTicket::create([
            'community_id' => $communityId,
            'title' => $validated['entryTitle'],
            'description' => $validated['entryDescription'],
            'type' => 'general',
            'attachment_path' => $this->entryFile?->store('comunigest/employee-tickets', 'public'),
            'priority' => 'normal',
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        $this->completeEmployeeEntry('Ticket enviado correctamente.', 'tickets');
    }

    private function renderOwner(): View
    {
        $person = CommunityPortalContext::person();
        abort_unless($person, 403);
        $person->load(['communities', 'properties.community','properties']);

        $search = mb_substr(trim($this->search), 0, 100);

        return view('livewire.community-portal', [
            'portalType' => 'owner', 'person' => $person,
            'documents' => CommunityOwnerDocument::with(['property', 'documentType'])->where('person_id', $person->id)
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest()->limit(20)->get(),
            'appointments' => CommunityAppointment::with(['community', 'department'])->where('person_id', $person->id)->where('starts_at', '>=', now()->subDay())
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->orderBy('starts_at')->limit(20)->get(),
            'tickets' => CommunityTicket::with(['community', 'property'])->where('person_id', $person->id)
                ->where('type', '!=', 'incident')
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest()->limit(20)->get(),
            'ownerIncidents' => Incident::with(['community', 'property','photos'])->where('person_id', $person->id)
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest()->limit(20)->get(),
            'fees' => $person->communityFees()->with(['community', 'property'])
                ->when($search !== '', fn ($query) => $query->where('concept', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest('period')->limit(20)->get(),
            'appointmentSlots' => $this->ownerCommunityId && $this->appointmentDate
                ? app(CommunityAppointmentAvailability::class)->slots($this->ownerCommunityId, $this->appointmentDate)
                : [],
            'workCategories' => WorkCategory::query()->where('active', true)->orderBy('sort')->orderBy('name')->get(['id', 'name']),
            'workCatalogs' => WorkCatalog::query()->where('active', true)->when($this->incidentWorkCategoryId, fn ($query) => $query->where('work_category_id', $this->incidentWorkCategoryId))->orderBy('title')->get(['id', 'work_category_id', 'title']),
        ]);
    }

    private function renderEmployee(): View
    {
        $employee = CommunityPortalContext::employee();
        abort_unless($employee, 403);
        $communityIds = CommunityPortalContext::employeeCommunityIds();
        $search = mb_substr(trim($this->search), 0, 100);
        $planId = $this->planId;

        return view('livewire.community-portal', [
            'portalType' => 'employee', 'employee' => $employee->load('communityDepartments.community'),
            'workCategories' => WorkCategory::query()->where('active', true)->orderBy('sort')->orderBy('name')->get(['id', 'name']),
            'workCatalogs' => WorkCatalog::query()->where('active', true)->when($this->incidentWorkCategoryId, fn ($query) => $query->where('work_category_id', $this->incidentWorkCategoryId))->orderBy('title')->get(['id', 'work_category_id', 'title']),
            'employeeCommunities' => Community::query()->whereKey($communityIds)->orderBy('name')->get(['id', 'name']),
            'shifts' => CommunityShift::with(['community', 'department', 'workOrder'])->where('employee_id', $employee->id)->where('shift_date', '>=', today())
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->orderBy('shift_date')->limit(20)->get(),
            'attendances' => CommunityAttendance::with(['community', 'communities', 'department'])->where('employee_id', $employee->id)
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest('attendance_date')->limit(20)->get(),
            'workOrders' => WorkOrder::with(['community', 'tasks', 'plan'])
                ->when($this->plansFilter !== '', fn ($query) => $query->where('community_plan_id', $this->plansFilter))
                ->when($this->planId, fn ($query) => $query->where('community_plan_id', $this->planId))
                ->when($this->sourceFilter !== '', fn ($query) => $query->where('source_type', $this->sourceFilter))
                ->where(function ($query) use ($employee, $communityIds): void {
                    $query->whereHas('communityShifts', fn ($shiftQuery) => $shiftQuery->where('employee_id', $employee->id))
                        ->orWhereIn('community_id', $communityIds);
                })->whereIn('status', ['pending', 'in_progress'])
                ->when($search !== '', fn ($query) => $query->where('code', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->orderBy('work_date')->limit(20)->get(),
            'plans' => CommunityPlan::with(['community', 'items', 'workOrders'])->whereIn('community_id', $communityIds)->where('status', 'active')
                ->when($this->communityFilter, fn ($query) => $query->where('community_id', $this->communityFilter))
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('valid_from')->limit(20)->get(),
            'incidents' => Incident::with(['community', 'workOrder', 'photos'])->where(function ($query) use ($communityIds): void {
                $query->whereIn('community_id', $communityIds)
                    ->orWhere(fn ($ownQuery) => $ownQuery->whereNull('community_id')->where('created_by', auth()->id()));
            })->whereNotIn('status', ['resolved', 'closed'])
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest()->limit(20)->get(),
            'tickets' => CommunityTicket::with(['community', 'property', 'person'])->whereIn('community_id', $communityIds)->where('type', '!=', 'expense')->whereNotIn('status', ['resolved', 'closed'])
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest()->limit(20)->get(),
            'employeeAppointments' => CommunityAppointment::with(['community', 'department'])->where('created_by', auth()->id())
                ->where(fn ($query) => $query->whereIn('community_id', $communityIds)->orWhereNull('community_id'))
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest('starts_at')->limit(20)->get(),
            'employeeDocuments' => CommunityEmployeeDocument::with(['community', 'workOrder'])->where('employee_id', $employee->id)
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest()->limit(20)->get(),
            'expenseTickets' => CommunityTicket::with('community')->where('created_by', auth()->id())->where('type', 'expense')
                ->where(fn ($query) => $query->whereIn('community_id', $communityIds)->orWhereNull('community_id'))
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest()->limit(20)->get(),
            'employeeTickets' => CommunityTicket::with('community')->where('created_by', auth()->id())->where('type', 'general')
                ->where(fn ($query) => $query->whereIn('community_id', $communityIds)->orWhereNull('community_id'))
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest()->limit(20)->get(),
            'appointmentSlots' => $this->appointmentDate
                ? app(CommunityAppointmentAvailability::class)->slots($this->employeeCommunityId, $this->appointmentDate)
                : [],
        ]);
    }

    private function authorizedOwnerCommunity(): array
    {
        abort_unless(CommunityPortalContext::isOwner(), 403);
        $person = CommunityPortalContext::person();
        abort_unless($person, 403);
        $validated = $this->validate(['ownerCommunityId' => ['required', 'integer']]);
        $community = $person->communities()->whereKey($validated['ownerCommunityId'])->firstOrFail();

        return [$person, $community];
    }

    private function authorizedOwnerProperty(): array
    {
        abort_unless(CommunityPortalContext::isOwner(), 403);
        $person = CommunityPortalContext::person();
        abort_unless($person, 403);
        $validated = $this->validate(['ticketPropertyId' => ['required', 'integer']]);
        $property = $person->properties()->whereKey($validated['ticketPropertyId'])->firstOrFail();
        abort_unless($property->community_id, 422, 'La propiedad no está vinculada a una comunidad.');

        return [$person, $property];
    }

    private function authorizedEmployee(): Employee
    {
        $employee = CommunityPortalContext::employee();
        abort_unless($employee, 403);

        return $employee;
    }

    private function authorizeEmployeeCommunity(int $communityId): void
    {
        abort_unless(CommunityPortalContext::employeeCommunityIds()->contains($communityId), 403);
    }

    private function completeEmployeeEntry(string $message, string $section): void
    {
        $this->reset('employeeCommunityId', 'employeeEntryType', 'receiptOcrData', 'entryTitle', 'entryDescription', 'appointmentStartsAt', 'appointmentDate', 'appointmentTime', 'incidentWorkOrderId', 'incidentPriority', 'incidentWorkCategoryId', 'incidentWorkCatalogId', 'expenseAmount', 'entryFile');
        $this->expenseInputMode = 'ocr';
        $this->selectOnlyEmployeeCommunity();
        $this->incidentPriority = 'normal';
        $this->message = $message;
        $this->section = $section;
        $this->dispatch('community-employee-entry-created');
    }

    private function completeOwnerEntry(string $message, string $section): void
    {
        $this->reset('ownerCommunityId', 'ticketPropertyId', 'entryTitle', 'entryDescription', 'appointmentStartsAt', 'appointmentDate', 'appointmentTime', 'incidentPriority', 'incidentWorkCategoryId', 'incidentWorkCatalogId', 'entryFile');
        $this->selectOnlyOwnerCommunity();
        $this->incidentPriority = 'normal';
        $this->message = $message;
        $this->section = $section;
        $this->dispatch('community-owner-entry-created');
    }

    private function selectOnlyEmployeeCommunity(): void
    {
        if (CommunityPortalContext::portalType() !== 'employee') {
            return;
        }

        $communityIds = CommunityPortalContext::employeeCommunityIds();
        $todayAttendance = CommunityPortalContext::employee()?->communityAttendances()
            ->whereDate('attendance_date', today())
            ->first();

        if ($todayAttendance) {
            $selectedCommunityIds = $todayAttendance->communities()->pluck('communities.id')->map(fn ($id): int => (int) $id)->all();
            $this->attendanceCommunityIds = $selectedCommunityIds ?: array_filter([(int) $todayAttendance->community_id]);
        }

        if ($communityIds->count() === 1) {
            $this->employeeCommunityId = (int) $communityIds->first();
            $this->attendanceCommunityIds = $this->attendanceCommunityIds ?: [(int) $communityIds->first()];
        }
    }

    private function selectOnlyOwnerCommunity(): void
    {
        if (CommunityPortalContext::portalType() !== 'owner') {
            return;
        }

        $person = CommunityPortalContext::person();
        $communityIds = $person?->communities()->pluck('communities.id');

        if ($communityIds?->count() === 1) {
            $this->ownerCommunityId = (int) $communityIds->first();
        }
    }
}
