<?php

declare(strict_types=1);

namespace App\Livewire\Nova;

use App\Domain\Nova\Missions\MissionResultBuilder;
use App\Domain\Nova\Missions\MissionRuntime;
use App\Domain\Nova\Studio\Workspace\WorkspaceRegistry;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

final class NovaWorkspace extends Component
{
    public string $prompt = '';

    public string $workspace = 'nova-demo';

    public string $business = 'Winery';

    public string $businessName = 'Mi negocio';

    public string $businessIcon = '✦';

    public string $userName = 'Patrick';

    /** @var array<int, array<string, string>> */
    public array $navigation = [];

    public bool $isWorkspaceEvolving = false;

    public ?string $newCapabilityId = null;

    public ?string $newCapabilityName = null;

    public string $status = 'ready';

    /** @var array<int, array<string, mixed>> */
    public array $missions = [];

    /** @var array<string, mixed>|null */
    public ?array $activeMission = null;

    /** @var array<string, mixed>|null */
    public ?array $activeResult = null;

    /** @var array<int, array<string, mixed>> */
    public array $completedResults = [];

    /** @var array<string, array{count: int, mission_id: string}> */
    public array $workspaceUpdates = [];

    /** @var array<string, mixed>|null */
    public ?array $activeWorkspaceArea = null;

    public string $activeTab = 'capabilities';

    public string $activeRepresentation = 'admin';

    /** @var array<int, array<string, mixed>> */
    public array $timeline = [];

    /** @var array<int, array<string, mixed>> */
    public array $events = [];

    /** @var array<int, array<string, mixed>> */
    public array $agents = [];

    /** @var array<int, array<string, mixed>> */
    public array $connectors = [];

    /** @var array<int, string> */
    public array $suggestions = [
        'Organizar las reservas de esta semana',
        'Preparar las facturas de este mes',
        'Sincronizar mi catálogo de productos',
    ];

    /** @var array<int, array{title: string, when: string}> */
    public array $recentWork = [
        ['title' => '12 facturas preparadas', 'when' => 'Ayer'],
        ['title' => 'Reservas sincronizadas', 'when' => 'Ayer'],
        ['title' => 'Catálogo de productos actualizado', 'when' => 'Ayer'],
    ];

    public function mount(): void
    {
        $requestedWorkspace = trim((string) request()->query('workspace', ''));

        if ($requestedWorkspace !== '') {
            $this->workspaceRegistry()->activate($requestedWorkspace);
        }

        $profile = session('nova.workspace');
        $transition = session()->pull('nova.workspace_transition');

        $this->userName = (string) (auth()->user()?->firstname ?? 'Patrick');

        if (is_array($profile)) {
            $this->businessName = $profile['business_name'] ?? $this->businessName;
            $this->businessIcon = $profile['business_icon'] ?? $this->businessIcon;
            $this->navigation = $profile['navigation'] ?? [];
            $this->business = (string) ($profile['blueprint_id'] ?? $profile['business_type'] ?? 'winery');
            $this->workspace = str($this->businessName)->slug()->toString();
        }

        $this->completedResults = session($this->historySessionKey(), []);
        $this->workspaceUpdates = session($this->updatesSessionKey(), []);

        if (is_array($transition) && ($transition['active'] ?? false)) {
            $this->isWorkspaceEvolving = true;
            $this->newCapabilityId = $transition['new_capability_id'] ?? null;
            $newCapability = collect($this->navigation)->firstWhere('id', $this->newCapabilityId);
            $this->newCapabilityName = is_array($newCapability) ? ($newCapability['name'] ?? null) : null;
        }

        $this->syncPresentation();
    }

    public function submitPrompt(): void
    {
        $goal = trim($this->prompt);

        if ($goal === '') {
            return;
        }

        $this->startMission($goal);
        $this->prompt = '';
    }

    #[On('nova-tool-selected')]
    public function activateTool(string $tool, string $area): void
    {
        $tool = trim($tool);
        $area = trim($area);

        if ($tool === '') {
            return;
        }

        $goal = $area === '' ? $tool : $tool.' en '.$area;
        $this->startMission($goal);
    }

    private function startMission(string $goal): void
    {
        $this->activeWorkspaceArea = null;
        $this->activeResult = null;
        $this->activeMission = $this->runtime()->detect($goal, $this->business);
        $this->missions[] = $this->activeMission;
        $this->syncPresentation();
        $this->dispatch('nova-runtime-updated', kind: 'mission', id: $this->activeMission['id']);
    }

    public function advanceMission(): void
    {
        if ($this->activeMission === null) {
            return;
        }

        $eventCount = count($this->activeMission['events']);
        $artifactCount = count($this->activeMission['artifacts']);

        $previousStatus = $this->activeMission['status'];
        $this->activeMission = $this->runtime()->tick($this->activeMission);
        $this->persistActiveMission();
        $this->syncPresentation();

        if ($previousStatus !== 'Completed' && $this->activeMission['status'] === 'Completed') {
            $this->recordCompletion();
        }

        if (count($this->events) > $eventCount) {
            $latestEvent = $this->events[array_key_last($this->events)];
            $this->dispatch('nova-runtime-updated', kind: 'event', id: $latestEvent['id']);
        }

        if (count($this->activeMission['artifacts']) > $artifactCount) {
            $latestArtifact = $this->activeMission['artifacts'][array_key_last($this->activeMission['artifacts'])];
            $this->dispatch('nova-runtime-updated', kind: 'artifact', id: $latestArtifact['id']);
        }
    }

    public function approveMission(): void
    {
        $this->applyRuntimeTransition(fn (array $mission): array => $this->runtime()->approve($mission));
    }

    public function rejectMission(): void
    {
        $this->applyRuntimeTransition(fn (array $mission): array => $this->runtime()->reject($mission));
    }

    public function editPlan(): void
    {
        $this->applyRuntimeTransition(fn (array $mission): array => $this->runtime()->revise($mission));
    }

    public function runShowcase(): void
    {
        $this->activeMission = null;
        $this->activeResult = null;
        $this->activeWorkspaceArea = null;
        $this->activeTab = 'capabilities';
        $this->activeRepresentation = 'admin';
        $this->timeline = [];
        $this->events = [];
        $this->status = 'ready';
        $this->syncPresentation();
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['capabilities', 'representations'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function setActiveRepresentation(string $representation): void
    {
        if (! in_array($representation, ['admin', 'web', 'copilot', 'mcp', 'ia'], true)) {
            return;
        }

        $this->activeRepresentation = $representation;
    }

    public function openResultArea(string $areaId): void
    {
        $area = collect($this->navigation)->firstWhere('id', $areaId);

        if (! is_array($area)) {
            return;
        }

        $this->activeWorkspaceArea = $area;
        $this->activeMission = null;
        $this->timeline = [];
        $this->events = [];
        $this->status = 'ready';
        $this->syncPresentation();
        $this->dispatch('nova-workspace-area-opened', areaId: $areaId, areaName: $area['name']);
    }

    public function startSuggestedMission(): void
    {
        $goal = trim((string) ($this->activeResult['suggested_goal'] ?? ''));

        if ($goal !== '') {
            $this->startMission($goal);
        }
    }

    public function runMissionFromExample(string $intentId): void
    {
        $workspace = session('nova.workspace', []);
        $intent = collect($workspace['representations']['copilot']['intents'] ?? [])
            ->firstWhere('id', $intentId);

        $goal = $intent['example'] ?? $intent['label'] ?? 'Ayudar con mi negocio';

        $this->startMission($goal);
    }

    public function render(): View
    {
        $workspaces = $this->workspaceRegistry()->all();

        return view('livewire.nova.nova-workspace')
            ->layout('layouts.nova', [
                'workspaceTransition' => [
                    'active' => $this->isWorkspaceEvolving,
                    'new_capability_id' => $this->newCapabilityId,
                ],
                'workspaceChoices' => $workspaces,
                'activeWorkspaceId' => session('nova.active_workspace_id'),
                'workspaceUpdates' => $this->workspaceUpdates,
            ]);
    }

    public function stateLabel(?string $state): string
    {
        return match ($state) {
            'Detected' => 'Detectada',
            'Planning' => 'Planificando',
            'Waiting Approval' => 'Esperando aprobación',
            'Running' => 'En ejecución',
            'Paused' => 'Pausada',
            'Completed' => 'Completada',
            'Failed' => 'Fallida',
            'Cancelled' => 'Cancelada',
            default => 'Preparada',
        };
    }

    /** @return array<int, array<string, mixed>> */
    public function workspaceSteps(): array
    {
        return array_map(static function (array $step): array {
            $step['agent'] = null;
            $step['connector'] = null;
            $step['duration'] = null;

            return $step;
        }, $this->timeline);
    }

    /** @return array<int, array<string, mixed>> */
    public function goalSteps(): array
    {
        $steps = array_values(array_filter(
            $this->workspaceSteps(),
            static fn (array $step): bool => ($step['capability_id'] ?? null) !== 'planning',
        ));

        $hasCurrentStep = collect($steps)->contains('status', 'running');
        $isPreparing = in_array($this->activeMission['status'] ?? null, ['Detected', 'Planning'], true);

        foreach ($steps as $index => $step) {
            $steps[$index]['title'] = $this->humanStepTitle(
                (string) ($step['capability_id'] ?? ''),
                (string) $step['title'],
            );

            if ($isPreparing && ! $hasCurrentStep && $index === 0) {
                $steps[$index]['status'] = 'running';
            }
        }

        return $steps;
    }

    private function humanStepTitle(string $id, string $fallback): string
    {
        return match ($id) {
            'synchronization' => 'Coordinando el trabajo',
            'messaging' => 'Preparando la confirmación',
            'crm' => 'Buscando al cliente',
            'calendar' => 'Revisando el calendario',
            'availability' => 'Comprobando disponibilidad',
            'hotel-reservations' => 'Preparando la reserva de habitación',
            'restaurant-reservations' => 'Preparando la reserva de mesa',
            'winery-reservations' => 'Preparando la visita a la bodega',
            'payments' => 'Preparando el pago',
            'invoices' => 'Preparando las facturas',
            'reporting' => 'Preparando el informe',
            'knowledge' => 'Buscando la información necesaria',
            default => $fallback,
        };
    }

    private function runtime(): MissionRuntime
    {
        return app(MissionRuntime::class);
    }

    private function workspaceRegistry(): WorkspaceRegistry
    {
        return app(WorkspaceRegistry::class);
    }

    private function resultBuilder(): MissionResultBuilder
    {
        return app(MissionResultBuilder::class);
    }

    private function recordCompletion(): void
    {                
        if ($this->activeMission === null) {
            return;
        }

        $result = $this->resultBuilder()->build($this->activeMission, $this->navigation)->toArray();
        $this->activeResult = $result;
        $this->completedResults = array_values(array_filter(
            $this->completedResults,
            static fn (array $stored): bool => $stored['mission_id'] !== $result['mission_id'],
        ));
        array_unshift($this->completedResults, $result);
        $this->completedResults = array_slice($this->completedResults, 0, 20);

        $areaId = $result['target_area_id'];
        $this->workspaceUpdates[$areaId] = [
            'count' => ($this->workspaceUpdates[$areaId]['count'] ?? 0) + 1,
            'mission_id' => $result['mission_id'],
        ];

        session()->put($this->historySessionKey(), $this->completedResults);
        session()->put($this->updatesSessionKey(), $this->workspaceUpdates);
    }

    private function historySessionKey(): string
    {
        return 'nova.mission_history.'.$this->workspace;
    }

    private function updatesSessionKey(): string
    {
        return 'nova.workspace_updates.'.$this->workspace;
    }

    /** @param callable(array<string, mixed>): array<string, mixed> $transition */
    private function applyRuntimeTransition(callable $transition): void
    {
        if ($this->activeMission === null) {
            return;
        }

        $this->activeMission = $transition($this->activeMission);
        $this->persistActiveMission();
        $this->syncPresentation();
        $this->dispatch('nova-runtime-updated', kind: 'state', id: $this->activeMission['id']);
    }

    private function persistActiveMission(): void
    {
        if ($this->activeMission === null) {
            return;
        }

        foreach ($this->missions as $index => $mission) {
            if ($mission['id'] === $this->activeMission['id']) {
                $this->missions[$index] = $this->activeMission;

                return;
            }
        }
    }

    private function syncPresentation(): void
    {
        $this->timeline = $this->activeMission['steps'] ?? [];
        $this->events = $this->activeMission['events'] ?? [];
        $this->status = $this->activeMission['status'] ?? 'ready';

        $runningStep = collect($this->timeline)->firstWhere('status', 'running');
        $lastEventByContext = collect($this->events)->keyBy('context');

        $this->agents = array_map(
            function (string $name) use ($runningStep, $lastEventByContext): array {
                $isRunning = ($runningStep['agent'] ?? null) === $name;
                $isPlanning = $name === 'Planificador' && ($this->activeMission['status'] ?? null) === 'Planning';
                $hasCompleted = collect($this->timeline)->contains(
                    fn (array $step): bool => $step['agent'] === $name && $step['status'] === 'completed',
                );

                return [
                    'name' => $name,
                    'status' => $isPlanning ? 'thinking' : ($isRunning ? $this->agentActivity($name) : ($hasCompleted ? 'completed' : 'waiting')),
                    'progress' => $isPlanning ? $this->activeMission['planner']['progress'] : ($isRunning ? $runningStep['progress'] : ($hasCompleted ? 100 : 0)),
                    'currentMission' => $this->activeMission['id'] ?? null,
                    'currentTool' => $isRunning ? $runningStep['tool'] : null,
                    'lastEvent' => $lastEventByContext->get($name)['title'] ?? null,
                ];
            },
            $this->activeMission['agents'] ?? [],
        );

        $this->connectors = array_map(
            function (string $provider) use ($runningStep, $lastEventByContext): array {
                $isOnline = ($runningStep['connector'] ?? null) === $provider;
                $latency = 28 + (abs(crc32($provider.($this->activeMission['tick'] ?? 0))) % 65);

                return [
                    'provider' => $provider,
                    'status' => $isOnline ? 'online' : 'standby',
                    'latency' => $latency.' ms',
                    'health' => $isOnline ? ($latency < 75 ? 'healthy' : 'degraded') : 'standby',
                    'currentRequest' => $isOnline ? $runningStep['tool'] : null,
                    'lastSync' => $lastEventByContext->get($provider)['time'] ?? null,
                    'currentMission' => $this->activeMission['id'] ?? null,
                ];
            },
            $this->activeMission['connectors'] ?? [],
        );
    }

    private function agentActivity(string $agent): string
    {
        return match ($agent) {
            'Planificador' => 'thinking',
            'Informes' => 'preparing',
            'WhatsApp' => 'executing',
            default => 'running',
        };
    }
}
