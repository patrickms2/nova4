<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions;

use App\Domain\Nova\Capabilities\MissionResolver;
use Illuminate\Support\Str;

final readonly class MissionRuntime
{
    public function __construct(
        private MissionTimeline $timeline,
        private MissionProgress $progress,
        private MissionResolver $resolver,
        private Executors\MissionStepExecutor $stepExecutor,
    ) {}

    /** @return array<string, mixed> */
    public function detect(string $goal, string $businessType, array $context = []): array
    {
        $now = now()->toIso8601String();
        $resolution = $this->resolver->resolve($goal, $businessType);
        $steps = $this->timeline->fromCapabilities($resolution['capabilities']);

        return [
            'id' => (string) Str::uuid(),
            'goal' => $goal,
            'context' => $context,
            'blueprint' => $resolution['blueprint'],
            'capabilities' => $resolution['capabilities'],
            'capability_graph' => array_map(
                static fn (array $capability): array => [
                    'id' => $capability['id'],
                    'name' => $capability['name'],
                    'dependencies' => $capability['dependencies'],
                ],
                $resolution['capabilities'],
            ),
            'status' => MissionState::Detected->value,
            'created_at' => $now,
            'updated_at' => $now,
            'started_at' => null,
            'completed_at' => null,
            'duration_seconds' => 0,
            'progress' => 0,
            'estimated_time' => $resolution['estimated_duration'].' seconds',
            'planner' => ['status' => 'detected', 'progress' => 0, 'locked' => false],
            'steps' => $steps,
            'agents' => $resolution['agents'],
            'connectors' => $resolution['connectors'],
            'providers' => $resolution['providers'],
            'events' => [
                $this->event('mission.created', 'Misión creada', $goal, 'Misión'),
                $this->event('capabilities.resolved', 'Capacidades resueltas', count($resolution['capabilities']).' capacidades seleccionadas para '.$resolution['blueprint']['name'].'.', 'Grafo de capacidades'),
            ],
            'artifacts' => [],
            'approval' => [
                'required' => $resolution['requires_approval'],
                'status' => 'not_requested',
            ],
            'result' => null,
            'tick' => 0,
        ];
    }

    /** @param array<string, mixed> $mission
     * @return array<string, mixed>
     */
    public function tick(array $mission): array
    {
        $state = MissionState::from($mission['status']);

        if (! $state->canAdvance()) {
            return $mission;
        }

        $mission['tick']++;
        $mission['duration_seconds']++;

        $mission = match ($state) {
            MissionState::Detected => $this->beginPlanning($mission),
            MissionState::Planning => $this->finishPlanning($mission),
            MissionState::Running => $this->execute($mission),
            default => $mission,
        };

        $mission['progress'] = $this->progress->calculate($mission['steps']);
        $mission['updated_at'] = now()->toIso8601String();

        return $mission;
    }

    /** @param array<string, mixed> $mission
     * @return array<string, mixed>
     */
    public function approve(array $mission): array
    {
        if ($mission['status'] !== MissionState::WaitingApproval->value) {
            return $mission;
        }

        $mission['approval']['status'] = 'approved';
        $mission['events'][] = $this->event('mission.approved', 'Misión aprobada', 'La ejecución ha sido autorizada.', 'Aprobación');

        return $this->start($mission);
    }

    /** @param array<string, mixed> $mission
     * @return array<string, mixed>
     */
    public function reject(array $mission): array
    {
        $mission['approval']['status'] = 'rejected';
        $mission['status'] = MissionState::Cancelled->value;
        $mission['events'][] = $this->event('mission.cancelled', 'Misión cancelada', 'El plan de ejecución ha sido rechazado.', 'Aprobación');

        return $mission;
    }

    /** @param array<string, mixed> $mission
     * @return array<string, mixed>
     */
    public function revise(array $mission): array
    {
        $mission['status'] = MissionState::Planning->value;
        $mission['approval']['status'] = 'editing';
        $mission['planner'] = ['status' => 'revising', 'progress' => 40, 'locked' => false];
        $mission['events'][] = $this->event('planner.revising', 'Revisión del plan iniciada', 'El Planificador está revisando el plan de ejecución.', 'Planificador');

        return $mission;
    }

    /** @param array<string, mixed> $mission
     * @return array<string, mixed>
     */
    private function beginPlanning(array $mission): array
    {
        $mission['status'] = MissionState::Planning->value;
        $mission['planner'] = ['status' => 'thinking', 'progress' => 45, 'locked' => false];
        $mission['events'][] = $this->event('planner.started', 'Planificador iniciado', 'Creando el plan de ejecución de la misión.', 'Planificador');

        return $mission;
    }

    /** @param array<string, mixed> $mission
     * @return array<string, mixed>
     */
    private function finishPlanning(array $mission): array
    {
        $mission['planner'] = ['status' => 'ready', 'progress' => 100, 'locked' => false];
        $mission['events'][] = $this->event('planner.finished', 'Planificación finalizada', 'El plan de ejecución y las asignaciones están preparados.', 'Planificador');

        if ($mission['approval']['required']) {
            $mission['status'] = MissionState::WaitingApproval->value;
            $mission['approval']['status'] = 'pending';
            $mission['events'][] = $this->event('approval.requested', 'Plan de ejecución preparado', 'Se requiere aprobación antes de iniciar la ejecución.', 'Aprobación');

            return $mission;
        }

        return $this->start($mission);
    }

    /** @param array<string, mixed> $mission
     * @return array<string, mixed>
     */
    private function start(array $mission): array
    {
        $mission['status'] = MissionState::Running->value;
        $mission['started_at'] ??= now()->toIso8601String();
        $mission['planner']['locked'] = true;
        $mission['events'][] = $this->event('runtime.started', 'Motor iniciado', 'Los agentes están comenzando la ejecución.', 'Motor');

        return $mission;
    }

    /** @param array<string, mixed> $mission
     * @return array<string, mixed>
     */
    private function execute(array $mission): array
    {
        $index = $this->currentStepIndex($mission['steps']);

        if ($index === null) {
            foreach ($mission['steps'] as $candidate => $step) {
                if ($step['status'] === 'waiting') {
                    $index = $candidate;
                    $mission['steps'][$index]['status'] = 'running';
                    $mission['steps'][$index]['progress'] = 10;
                    $mission['events'][] = $this->event('agent.started', 'Agente iniciado', $step['title'], $step['agent']);

                    if ($step['connector']) {
                        $mission['events'][] = $this->event('connector.online', 'Conector en línea', $step['connector'].' funciona correctamente.', $step['connector']);
                    }

                    return $mission;
                }
            }

            return $this->complete($mission);
        }

        $step = $mission['steps'][$index];

        if ($this->stepExecutor->supports($step, $mission)) {
            $mission = $this->stepExecutor->execute($step, $mission);
            $mission['progress'] = $this->progress->calculate($mission['steps']);

            return $mission;
        }

        $nextProgress = min(100, $step['progress'] + 30);
        $mission['steps'][$index]['progress'] = $nextProgress;

        if ($step['connector'] && $nextProgress === 40) {
            $mission['events'][] = $this->event('connector.request', 'Solicitud del conector', $step['tool'], $step['connector']);
        }

        if ($step['connector'] && $nextProgress === 70) {
            $mission['events'][] = $this->event('connector.response', 'Respuesta del conector', '200 OK · respuesta simulada recibida.', $step['connector']);
        }

        if ($nextProgress < 100) {
            return $mission;
        }

        $mission['steps'][$index]['status'] = 'completed';
        $artifact = MissionArtifact::make($step['artifact'], $mission['goal'])->toArray();
        $mission['artifacts'][] = $artifact;
        $mission['events'][] = $this->event('artifact.generated', 'Artefacto generado', $artifact['name'], $step['agent']);
        $mission['events'][] = $this->event('step.completed', 'Ejecución finalizada', $step['title'], $step['agent']);

        return $mission;
    }

    /** @param array<string, mixed> $mission
     * @return array<string, mixed>
     */
    private function complete(array $mission): array
    {
        $mission['status'] = MissionState::Completed->value;
        $mission['progress'] = 100;
        $mission['completed_at'] = now()->toIso8601String();
        $mission['result'] = 'Misión completada correctamente en modo demostración.';
        $mission['events'][] = $this->event('mission.completed', 'Misión completada', $mission['result'], 'Misión');

        return $mission;
    }

    /** @param array<int, array<string, mixed>> $steps */
    private function currentStepIndex(array $steps): ?int
    {
        foreach ($steps as $index => $step) {
            if ($step['status'] === 'running') {
                return $index;
            }
        }

        return null;
    }

    /** @return array<string, string> */
    private function event(string $type, string $title, string $description, string $context): array
    {
        return MissionEvent::make($type, $title, $description, $context)->toArray();
    }
}
