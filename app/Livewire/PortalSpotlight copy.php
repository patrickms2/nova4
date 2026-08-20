<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Filament\App\Resources\TaxistaAppointments\Schemas\TaxistaAppointmentForm;
use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentForm;
use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use App\Filament\App\Resources\TaxistaExpenses\TaxistaExpenseResource;
use App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource;
use App\Filament\App\Resources\TaxistaTickets\Schemas\TaxistaTicketForm;
use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Models\BookingDepartment;
use App\Models\Taxista;
use App\Models\TaxistaAppointment;
use App\Models\TaxistaDocument;
use App\Models\TaxistaExpense;
use App\Models\TaxistaTaxi;
use App\Models\TaxistaTicket;
use App\Support\PortalTaxistaContext;
use DateTimeInterface;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class PortalSpotlight extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithFileUploads;

    public bool $showSpotlight = false;

    public string $spotlight = '';

    public function openSpotlight(): void
    {
        $this->showSpotlight = true;
    }

    public function closeSpotlight(): void
    {
        $this->showSpotlight = false;
        $this->spotlight = '';
    }

    public function runQuickAction(string $actionName): void
    {
        $allowed = [
            'createCita',
            'createDocumento',
            'createIncidencia',
            // 'createSugerencia',
            'createEntradaSalida',
            'cargarAvisos',
            'cargarAyuda',
            'createTicket',
        ];

        if (! in_array($actionName, $allowed, true)) {
            return;
        }

        $this->closeSpotlight();

        if (in_array($actionName, ['createCita', 'createDocumento', 'createIncidencia', 'createTicket', 'createEntradaSalida', 'cargarAvisos', 'cargarAyuda'], true)) {
            $this->mountAction($actionName.'Action');
        }
    }

    public function createEntradaSalida(): Action
    {
        return CreateAction::make('createEntradaSalidaAction')
            ->label('Registro E/S')
            ->model(TaxistaDocument::class)
            ->form(fn (Schema $schema): Schema => PortalTimeClock::class)
            ->createAnother(false)
            ->mutateFormDataUsing(function (array $data): array {
                $taxistaId = PortalTaxistaContext::taxistaUserId();
                $data['uploaded_by_user_id'] = $data['uploaded_by_user_id'] ?? $taxistaId;
                $data['taxista_user_id'] = $taxistaId;

                return $data;
            })
            ->modalHeading('Registro Entrada/Salida');
    }

    public function cargarAvisos(): Action
    {
        return CreateAction::make('cargarAvisosAction')
            ->label('Avisos')
            ->model(TaxistaDocument::class)
            ->form(fn (Schema $schema): Schema => PortalTimeClock)
            ->createAnother(false)
            ->mutateFormDataUsing(function (array $data): array {
                $taxistaId = PortalTaxistaContext::taxistaUserId();
                $data['uploaded_by_user_id'] = $data['uploaded_by_user_id'] ?? $taxistaId;
                $data['taxista_user_id'] = $taxistaId;

                return $data;
            })
            ->modalHeading('Avisos');
    }

    public function cargarAyuda(): Action
    {
        return CreateAction::make('cargarAyudaAction')
            ->label('Ayuda')
            ->model(TaxistaDocument::class)
            ->form(fn (Schema $schema): Schema => PortalTimeClock)
            ->createAnother(false)
            ->mutateFormDataUsing(function (array $data): array {
                $taxistaId = PortalTaxistaContext::taxistaUserId();
                $data['uploaded_by_user_id'] = $data['uploaded_by_user_id'] ?? $taxistaId;
                $data['taxista_user_id'] = $taxistaId;

                return $data;
            })
            ->modalHeading('Ayuda');
    }

    public function createIncidenciaAction(): Action
    {
        return $this->makeQuickTicketAction(
            'createIncidenciaAction',
            'INCIDENCIA',
            'errores',
            'alta',
            now()->endOfDay(),
        );
    }

    public function createSugerenciaAction(): Action
    {
        return $this->makeQuickTicketAction(
            'createSugerenciaAction',
            'SUGERENCIA',
            'sugerencia',
            'baja',
            null,
        );
    }

    public function createTicketAction(): Action
    {
        return CreateAction::make('createTicketAction')
            ->label('Nuevo Ticket')
            ->model(TaxistaTicket::class)
            ->form(fn (Schema $schema): Schema => TaxistaTicketForm::configure($schema))
            ->createAnother(false)
            ->modalSubmitActionLabel('Crear')
            ->modalCancelActionLabel('Cancelar')
            ->closeModalByClickingAway(true)
            ->closeModalByEscaping(true)
            ->mutateFormDataUsing(function (array $data): array {
                $taxistaId = PortalTaxistaContext::taxistaUserId();
                $data['created_by_user_id'] = $data['created_by_user_id'] ?? $taxistaId;
                $data['user_id'] = $taxistaId;

                return $data;
            })
            ->after(function (): void {
                $this->dispatch('refreshPortal');
            })
            ->modalHeading('Nuevo Ticket');
    }

    public function createCitaAction(): Action
    {
        return CreateAction::make('createCitaAction')
            ->label('Nueva Cita')
            ->model(TaxistaAppointment::class)
            ->form(fn (Schema $schema): Schema => TaxistaAppointmentForm::configure($schema))
            ->createAnother(false)
            ->modalSubmitActionLabel('Crear')
            ->modalCancelActionLabel('Cancelar')
            ->closeModalByClickingAway(true)
            ->closeModalByEscaping(true)
            ->mutateFormDataUsing(function (array $data): array {
                $taxistaId = PortalTaxistaContext::taxistaUserId();
                $data['created_by_user_id'] = $data['created_by_user_id'] ?? $taxistaId;
                $data['taxista_user_id'] = $taxistaId;

                return $data;
            })
            ->after(function (): void {
                $this->dispatch('refreshPortal');
            })
            ->modalHeading('Nueva Cita');
    }

    public function createDocumentoAction(): Action
    {
        return CreateAction::make('createDocumentoAction')
            ->label('Nuevo documento')
            ->model(TaxistaDocument::class)
            ->form(fn (Schema $schema): Schema => TaxistaDocumentForm::configure($schema))
            ->createAnother(false)
            ->modalSubmitActionLabel('Crear')
            ->modalCancelActionLabel('Cancelar')
            ->closeModalByClickingAway(true)
            ->closeModalByEscaping(true)
            ->mutateFormDataUsing(function (array $data): array {
                $taxistaId = PortalTaxistaContext::taxistaUserId();
                $data['uploaded_by_user_id'] = $data['uploaded_by_user_id'] ?? $taxistaId;
                $data['taxista_user_id'] = $taxistaId;

                return $data;
            })
            ->after(function (): void {
                $this->dispatch('refreshPortal');
            })
            ->modalHeading('Nuevo documento');
    }

    public function shouldShowCreateCitaSubmit(): bool
    {
        return filled($this->getMountedActionDataValue('createCitaAction', 'starts_at'));
    }

    public function shouldShowCreateDocumentoSubmit(): bool
    {
        return filled($this->getMountedActionDataValue('createDocumentoAction', 'file_path'));
    }

    public function shouldShowCreateTicketSubmit(): bool
    {
        return filled($this->getMountedActionDataValue('createTicketAction', 'priority'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function spotlightResults(): array
    {
        if (strlen($this->spotlight) < 2) {
            return [];
        }

        $taxistaId = PortalTaxistaContext::taxistaUserId();

        if (! $taxistaId) {
            return [];
        }

        ['type' => $typeFilter, 'raw' => $rawNeedle, 'compact' => $compactNeedle] = $this->parseSpotlightQuery($this->spotlight);
        $matchesTaxistaIdentity = $this->matchesSpotlightNeedle($this->currentSpotlightIdentityAliases($taxistaId), $rawNeedle, $compactNeedle);
        $results = [];

        if (($typeFilter === null || $typeFilter === 'cita') && DbSchema::hasTable('taxista_appointments')) {
            TaxistaAppointment::query()
                ->select(['id', 'taxista_user_id', 'title', 'starts_at', 'status', 'booking_department_id', 'tipo_cita_id'])
                ->with(['department:id,name', 'tipo:id,nombre'])
                ->where('taxista_user_id', $taxistaId)
                ->latest('starts_at')
                ->limit(50)
                ->get()
                ->filter(fn (TaxistaAppointment $appointment): bool => $matchesTaxistaIdentity || $this->matchesSpotlightNeedle(
                    [
                        $appointment->title,
                        $appointment->status,
                        $appointment->department?->name,
                        $appointment->tipo?->nombre,
                    ],
                    $rawNeedle,
                    $compactNeedle,
                ))
                ->take(4)
                ->each(function (TaxistaAppointment $appointment) use (&$results, $rawNeedle, $compactNeedle): void {
                    $matchContext = $this->spotlightMatchContext(
                        [
                            'Cita' => $appointment->title,
                            'Estado' => $appointment->status,
                            'Departamento' => $appointment->department?->name,
                            'Tipo' => $appointment->tipo?->nombre,
                        ],
                        $rawNeedle,
                        $compactNeedle,
                    );

                    $results[] = [
                        'type' => 'cita',
                        'id' => $appointment->id,
                        'label' => $appointment->title,
                        'sub' => collect([
                            $matchContext,
                            $appointment->starts_at?->format('d/m/Y H:i'),
                        ])->filter()->implode(' · '),
                        'url' => $this->safeResourceUrl(TaxistaAppointmentResource::class, 'view', (int) $appointment->id),
                    ];
                });
        }

        if (($typeFilter === null || $typeFilter === 'documento') && DbSchema::hasTable('taxista_documents')) {
            $this->baseDocumentsQuery($taxistaId)
                ->limit(50)
                ->get()
                ->filter(fn (TaxistaDocument $document): bool => $matchesTaxistaIdentity || $this->matchesSpotlightNeedle(
                    [
                        $document->title,
                        $document->document_type,
                        $document->status,
                    ],
                    $rawNeedle,
                    $compactNeedle,
                ))
                ->take(4)
                ->each(function (TaxistaDocument $document) use (&$results, $rawNeedle, $compactNeedle): void {
                    $matchContext = $this->spotlightMatchContext(
                        [
                            'Documento' => $document->title,
                            'Tipo' => $document->document_type,
                            'Estado' => $document->status,
                        ],
                        $rawNeedle,
                        $compactNeedle,
                    );

                    $results[] = [
                        'type' => 'documento',
                        'id' => $document->id,
                        'label' => $document->title,
                        'sub' => collect([
                            $matchContext,
                        ])->filter()->implode(' · '),
                        'url' => $this->safeResourceUrl(TaxistaDocumentResource::class, 'view', (int) $document->id),
                    ];
                });
        }

        if (DbSchema::hasTable('taxista_expenses')) {
            TaxistaExpense::query()
                ->select(['id', 'taxista_user_id', 'title', 'status'])
                ->where('taxista_user_id', $taxistaId)
                ->latest('id')
                ->limit(30)
                ->get()
                ->filter(fn (TaxistaExpense $expense): bool => $this->matchesSpotlightNeedle(
                    [
                        $expense->title,
                        (string) ($expense->status?->value ?? 'pendiente'),
                    ],
                    $rawNeedle,
                    $compactNeedle,
                ))
                ->take(4)
                ->each(function (TaxistaExpense $expense) use (&$results, $rawNeedle, $compactNeedle): void {
                    $matchContext = $this->spotlightMatchContext(
                        [
                            'Gasto' => $expense->title,
                            'Estado' => (string) ($expense->status?->value ?? 'pendiente'),
                        ],
                        $rawNeedle,
                        $compactNeedle,
                    );

                    $results[] = [
                        'type' => 'gasto',
                        'id' => $expense->id,
                        'label' => $expense->title,
                        'sub' => $matchContext,
                        'url' => $this->safeResourceUrl(TaxistaExpenseResource::class, 'edit', (int) $expense->id),
                    ];
                });
        }

        if (($typeFilter === null || $typeFilter === 'ticket') && DbSchema::hasTable('taxista_tickets')) {
            TaxistaTicket::query()
                ->select(['id', 'user_id', 'title', 'status', 'priority', 'booking_department_id'])
                ->with(['department:id,name'])
                ->where('user_id', $taxistaId)
                ->latest('opened_at')
                ->limit(50)
                ->get()
                ->filter(fn (TaxistaTicket $ticket): bool => $matchesTaxistaIdentity || $this->matchesSpotlightNeedle(
                    [
                        $ticket->title,
                        $ticket->status,
                        $ticket->priority,
                        $ticket->department?->name,
                    ],
                    $rawNeedle,
                    $compactNeedle,
                ))
                ->take(4)
                ->each(function (TaxistaTicket $ticket) use (&$results, $rawNeedle, $compactNeedle): void {
                    $matchContext = $this->spotlightMatchContext(
                        [
                            'Ticket' => $ticket->title,
                            'Estado' => $ticket->status,
                            'Prioridad' => $ticket->priority,
                            'Departamento' => $ticket->department?->name,
                        ],
                        $rawNeedle,
                        $compactNeedle,
                    );

                    $results[] = [
                        'type' => 'ticket',
                        'id' => $ticket->id,
                        'label' => $ticket->title,
                        'sub' => collect([
                            $matchContext,
                            $ticket->status,
                        ])->filter()->implode(' · '),
                        'url' => $this->safeResourceUrl(TaxistaTicketResource::class, 'view', (int) $ticket->id),
                    ];
                });
        }

        if (($typeFilter === null || $typeFilter === 'taxi') && DbSchema::hasTable('taxista_taxis')) {
            TaxistaTaxi::query()
                ->select(['id', 'taxista_user_id', 'license_plate', 'tracking_uuid', 'vehicle_brand', 'vehicle_model'])
                ->where('taxista_user_id', $taxistaId)
                ->latest('id')
                ->limit(30)
                ->get()
                ->filter(fn (TaxistaTaxi $taxi): bool => $this->matchesSpotlightNeedle(
                    [
                        $taxi->license_plate,
                        $taxi->tracking_uuid,
                        $taxi->vehicle_brand,
                        $taxi->vehicle_model,
                    ],
                    $rawNeedle,
                    $compactNeedle,
                ))
                ->take(4)
                ->each(function (TaxistaTaxi $taxi) use (&$results, $rawNeedle, $compactNeedle): void {
                    $label = $taxi->license_plate ?: ('Taxi '.$taxi->id);
                    $matchContext = $this->spotlightMatchContext(
                        [
                            'Matricula' => $taxi->license_plate,
                            'UUID' => $taxi->tracking_uuid,
                            'Marca' => $taxi->vehicle_brand,
                            'Modelo' => $taxi->vehicle_model,
                        ],
                        $rawNeedle,
                        $compactNeedle,
                    );
                    $sub = trim(implode(' ', array_filter([$taxi->vehicle_brand, $taxi->vehicle_model])));

                    $results[] = [
                        'type' => 'taxi',
                        'id' => $taxi->id,
                        'label' => $label,
                        'sub' => collect([
                            $matchContext,
                            $sub !== '' ? $sub : $taxi->tracking_uuid,
                        ])->filter()->implode(' · '),
                        'url' => $this->safeResourceUrl(TaxistaTaxiResource::class, 'edit', (int) $taxi->id),
                    ];
                });
        }

        return $results;
    }

    /**
     * @return array{type:?string,raw:string,compact:string}
     */
    private function parseSpotlightQuery(string $query): array
    {
        $raw = trim(Str::lower($query));
        $type = null;

        foreach (['cita' => 'cit', 'ticket' => 'tic', 'documento' => 'doc', 'taxi' => 'tax'] as $candidateType => $prefix) {
            if (Str::startsWith($raw, $prefix) && strlen($raw) > strlen($prefix)) {
                $type = $candidateType;
                $raw = ltrim(substr($raw, strlen($prefix)));
                break;
            }
        }

        return [
            'type' => $type,
            'raw' => $raw,
            'compact' => $this->normalizeSpotlightValue($raw),
        ];
    }

    /**
     * @param  array<int, string|null>  $haystacks
     */
    private function matchesSpotlightNeedle(array $haystacks, string $rawNeedle, string $compactNeedle): bool
    {
        foreach ($haystacks as $haystack) {
            $value = Str::lower((string) $haystack);

            if ($rawNeedle !== '' && str_contains($value, $rawNeedle)) {
                return true;
            }

            if ($compactNeedle !== '' && str_contains($this->normalizeSpotlightValue($value), $compactNeedle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, string|null>  $candidates
     */
    private function spotlightMatchContext(array $candidates, string $rawNeedle, string $compactNeedle): ?string
    {
        foreach ($candidates as $label => $value) {
            $normalizedValue = Str::lower((string) $value);

            if ($rawNeedle !== '' && str_contains($normalizedValue, $rawNeedle)) {
                return $label.' · '.(string) $value;
            }

            if ($compactNeedle !== '' && str_contains($this->normalizeSpotlightValue($normalizedValue), $compactNeedle)) {
                return $label.' · '.(string) $value;
            }
        }

        return null;
    }

    private function normalizeSpotlightValue(?string $value): string
    {
        return Str::lower(preg_replace('/[^a-z0-9]+/', '', Str::ascii((string) $value)) ?? '');
    }

    /**
     * @return array<int, string|null>
     */
    private function currentSpotlightIdentityAliases(int $taxistaId): array
    {
        $taxista = Taxista::query()
            ->with([
                'municipio:id,nombre',
                'taxis:id,taxista_user_id,license_plate',
            ])
            ->find($taxistaId);

        if (! $taxista) {
            return [];
        }

        return [
            $taxista->name,
            $taxista->licencia,
            $taxista->municipio?->nombre,
            ...$taxista->taxis->pluck('license_plate')->all(),
        ];
    }

    private function getMountedActionDataValue(string $expectedActionName, string $key): mixed
    {
        $mountedAction = $this->mountedActions[0][0] ?? null;

        if (! is_array($mountedAction)) {
            return null;
        }

        if (($mountedAction['name'] ?? null) !== $expectedActionName) {
            return null;
        }

        $data = $mountedAction['data'] ?? [];

        return is_array($data) ? data_get($data, $key) : null;
    }

    public function render(): View
    {
        $spotlightResults = $this->shouldLoadSpotlightResults()
            ? $this->spotlightResults()
            : [];

        return view('livewire.portal-spotlight', [
            'spotlightResults' => $spotlightResults,
        ]);
    }

    private function makeQuickTicketAction(
        string $actionName,
        string $label,
        string $ticketType,
        string $priority,
        ?DateTimeInterface $dueAt,
    ): Action {
        return CreateAction::make($actionName)
            ->label($label)
            ->model(TaxistaTicket::class)
            ->createAnother(false)
            ->form(function (Schema $schema) use ($label): array {
                return [
                    Hidden::make('ticket_type'),
                    Hidden::make('booking_department_id'),
                    Hidden::make('priority'),
                    Hidden::make('due_at'),
                    Hidden::make('status'),
                    Hidden::make('title'),
                    Textarea::make('description')
                        ->label('Descripcion')
                        ->rows(5)
                        ->required()
                        ->extraAttributes([
                            'x-effect' => "
                                const footer = \$el.closest('.fi-modal-window')?.querySelector('.fi-modal-footer');
                                if (! footer) return;
                                const description = \$wire.mountedActions?.[0]?.data?.description ?? '';
                                footer.style.display = description.trim().length > 2 ? 'flex' : 'none';
                            ",
                        ])
                        ->helperText("Describe {$label} y al guardar se pedirá la captura de pantalla."),
                ];
            })
            ->mutateFormDataUsing(function (array $data) use ($label, $ticketType, $priority, $dueAt): array {
                $taxistaId = PortalTaxistaContext::taxistaUserId();
                $departmentId = $this->resolveSupportDepartmentId()
                    ?? auth()->user()?->booking_department_id;

                $data['created_by_user_id'] = $data['created_by_user_id'] ?? $taxistaId;
                $data['user_id'] = $taxistaId;
                $data['ticket_type'] = $ticketType;
                $data['booking_department_id'] = $departmentId;
                $data['priority'] = $priority;
                $data['due_at'] = $dueAt;
                $data['status'] = 'abierto';
                $data['title'] = $this->composeQuickTicketTitle($label);

                return $data;
            })
            ->after(function (CreateAction $action, TaxistaTicket $record) use ($label): void {
                $this->dispatch('portal-screenshot-capture', ticketId: $record->id, label: $label);
            })
            ->modalHeading($label)
            ->modalSubmitActionLabel('Crear ticket')
            ->modalCancelActionLabel('Cancelar');
    }

    private function composeQuickTicketTitle(string $label): string
    {
        return sprintf('%s - PANTALLA %s', strtoupper($label), now()->format('Y-m-d'));
    }

    private function resolveSupportDepartmentId(): ?int
    {
        if (! DbSchema::hasTable('booking_departments')) {
            return null;
        }

        return BookingDepartment::query()
            ->when(DbSchema::hasColumn('booking_departments', 'has_tickets_service'), function (Builder $query): Builder {
                return $query->where('has_tickets_service', true);
            })
            ->where(function (Builder $query): void {
                $query->where('slug', 'soporte')
                    ->orWhere('name', 'like', '%soporte%')
                    ->orWhere('name', 'like', '%support%');
            })
            ->orderBy('name')
            ->value('id');
    }

    private function shouldLoadSpotlightResults(): bool
    {
        return $this->showSpotlight || strlen($this->spotlight) >= 2;
    }

    private function baseDocumentsQuery(int $taxistaId): Builder
    {
        return TaxistaDocument::query()
            ->select([
                'id',
                'taxista_user_id',
                'title',
                'document_type',
                'file_path',
                'status',
                'uploaded_at',
                'created_at',
            ])
            ->where('taxista_user_id', $taxistaId)
            ->where(function (Builder $query): void {
                $query->whereNull('status')->orWhere('status', '!=', 'archivado');
            });
    }

    /**
     * @param  class-string  $resourceClass
     */
    private function safeResourceUrl(string $resourceClass, string $page, int $recordId, ?string $fallback = null): string
    {
        try {
            /** @phpstan-ignore-next-line */
            return $resourceClass::getUrl($page, ['record' => $recordId], panel: 'portal');
        } catch (\Throwable) {
            if (is_string($fallback) && $fallback !== '') {
                return $fallback;
            }

            return '#';
        }
    }
}
