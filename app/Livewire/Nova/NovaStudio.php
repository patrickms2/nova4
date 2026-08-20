<?php

declare(strict_types=1);

namespace App\Livewire\Nova;

use App\Domain\Nova\Missions\MissionRuntime;
use App\Domain\Nova\Studio\Workspace\Capabilities\CapabilityCatalog;
use App\Domain\Nova\Studio\Workspace\DataSources\DataSourceCatalog;
use App\Domain\Nova\Studio\Workspace\WorkspaceBuilder;
use App\Domain\Nova\Studio\Workspace\WorkspaceEvolution;
use App\Domain\Nova\Studio\Workspace\WorkspaceRegistry;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class NovaStudio extends Component
{
    public string $step = 'welcome';

    /** @var array<int, string> */
    public array $activities = [];

    public ?string $businessType = null;

    public ?string $professionalActivity = null;

    public ?string $professionalVariant = null;

    /** @var array<int, string> */
    public array $professionalVariants = [];

    /** @var array<int, string> */
    public array $extraImprovements = [];

    /** @var array<int, string> */
    public array $objectives = [];

    /** @var array<int, string> */
    public array $tools = [];

    public ?bool $hasWebsite = null;

    public string $websiteUrl = '';

    public string $workspaceName = '';

    public int $sequence = 0;

    public string $importMode = 'import';

    /** @var array<string, mixed>|null */
    public ?array $workspacePreview = null;

    /** @var array<int, array<string, mixed>> */
    public array $workspaces = [];

    public ?string $selectedImprovementId = null;

    public ?string $newImprovementId = null;

    public ?string $evolvedAreaId = null;

    public ?array $discoveryMission = null;

    /** @var array<string, mixed> */
    public array $discoveredFacts = [];

    public ?string $previewNotice = null;

    public function mount(): void
    {
        $this->refreshWorkspaces();
        $workspace = $this->registry()->active();

        if ($workspace === null) {
            return;
        }

        $this->loadWorkspace($workspace);
        $this->step = 'overview';
    }

    public function start(): void
    {
        $this->step = 'activity';
    }

    public function toggleActivity(string $id): void
    {
        $this->activities = $this->toggle($this->activities, $id);

        if (count($this->activities) >= 2) {
            $this->step = 'business';
        }
    }

    public function selectBusiness(string $id): void
    {
        $this->businessType = $id;
        $this->sequence = 0;
        $this->step = $id === 'professional' ? 'professional-activity' : 'objectives';
    }

    public function selectProfessionalActivity(string $id): void
    {
        if (! array_key_exists($id, app(CapabilityCatalog::class)->professionalActivities())) {
            return;
        }

        $this->professionalActivity = $id;
        $this->professionalVariant = null;
        $this->professionalVariants = [];
        $this->step = 'professional-variant';
    }

    public function selectProfessionalVariant(string $id): void
    {
        $variant = app(CapabilityCatalog::class)->professionalVariant($id);

        if ($variant === null || $variant['activity'] !== $this->professionalActivity) {
            return;
        }

        $this->professionalVariants = $this->toggle($this->professionalVariants, $id);
        $this->professionalVariant = $this->professionalVariants[0] ?? null;
    }

    public function confirmProfessionalVariants(): void
    {
        if ($this->professionalVariants === []) {
            return;
        }

        $this->sequence = 0;
        $this->step = 'objectives';
    }

    public function toggleObjective(string $id): void
    {
        $objectives = $this->catalog()->objectives();
        if (! array_key_exists($id, $objectives)) {
            return;
        }

        $this->objectives = $this->toggle($this->objectives, $id);
    }

    public function confirmObjectives(): void
    {
        if ($this->objectives === []) {
            return;
        }

        $this->sequence = 0;
        $this->step = 'tools';
    }

    public function toggleTool(string $id): void
    {
        $tools = $this->dataSources()->tools();
        if (! array_key_exists($id, $tools)) {
            return;
        }

        $this->tools = $this->toggle($this->tools, $id);
    }

    public function confirmTools(): void
    {
        $this->sequence = 0;
        $this->step = 'website';
    }

    public function confirmConfiguration(): void
    {
        $this->preparePreview();
        $this->step = 'confirmation';
    }

    public function advanceSequence(): void
    {
        $limits = ['thinking' => 6, 'building' => 6, 'evolving' => 4];

        if ($this->step === 'discovery') {
            $this->advanceDiscoveryMission();

            return;
        }

        if (! isset($limits[$this->step])) {
            return;
        }

        $this->sequence++;

        if ($this->sequence < $limits[$this->step]) {
            return;
        }

        match ($this->step) {
            'thinking' => $this->finishThinking(),
            'building' => $this->finishBuilding(),
            'evolving' => $this->completeEvolution(),
        };
    }

    private function advanceDiscoveryMission(): void
    {
        if ($this->discoveryMission === null) {
            return;
        }

        $this->discoveryMission = $this->missionRuntime()->tick($this->discoveryMission);

        if ($this->discoveryMission['status'] !== 'Completed') {
            return;
        }

        $facts = $this->discoveryMission['context']['discovered_facts'] ?? null;
        if ($facts !== null) {
            $this->websiteUrl = (string) ($facts['url'] ?? $this->websiteUrl);
            $this->discoveredFacts = $facts;
            if ($this->workspacePreview !== null) {
                $this->workspacePreview['website'] = $this->websiteUrl;
                $this->workspacePreview['discovered_facts'] = $facts;
            }
        }

        $this->step = 'import';
    }

    public function acceptProposal(): void
    {
        $this->step = 'website';
    }

    public function requestMore(): void
    {
        $this->step = 'improvements';
    }

    public function toggleImprovement(string $id): void
    {
        $this->extraImprovements = $this->toggle($this->extraImprovements, $id);
        $this->preparePreview();
    }

    public function confirmImprovements(): void
    {
        $this->step = 'website';
    }

    public function improveWorkspace(string $id): void
    {
        if ($this->workspacePreview === null) {
            return;
        }

        $availableIds = array_column($this->evolution()->recommendations($this->workspacePreview), 'id');

        if (! in_array($id, $availableIds, true)) {
            return;
        }

        $this->selectedImprovementId = $id;
        $this->sequence = 0;
        $this->step = 'evolving';
    }

    public function openRepresentations(): void
    {
        if ($this->workspacePreview === null) {
            return;
        }

        $this->previewNotice = null;
        $this->step = 'representations';
    }

    public function returnToOverview(): void
    {
        $this->selectedImprovementId = null;
        $this->previewNotice = null;
        $this->step = 'overview';
    }

    public function editWorkspace(): void
    {
        if ($this->workspacePreview === null) {
            return;
        }

        $this->loadWorkspace($this->workspacePreview);
        $this->step = 'edit';
    }

    public function selectEditedBusiness(string $id): void
    {
        $this->businessType = $id;

        if ($id === 'professional' && $this->professionalVariant === null) {
            $this->professionalActivity = 'sales';
            $this->professionalVariant = app(CapabilityCatalog::class)->professionalVariants('sales')[0]['id'] ?? null;
            $this->professionalVariants = array_filter([$this->professionalVariant]);
        }

        $this->prepareEditedPreview();
    }

    public function selectEditedProfessionalActivity(string $id): void
    {
        $variants = app(CapabilityCatalog::class)->professionalVariants($id);

        if ($variants === []) {
            return;
        }

        $this->professionalActivity = $id;
        $this->professionalVariant = $variants[0]['id'];
        $this->professionalVariants = [$variants[0]['id']];
        $this->prepareEditedPreview();
    }

    public function selectEditedProfessionalVariant(string $id): void
    {
        $variant = app(CapabilityCatalog::class)->professionalVariant($id);

        if ($variant === null || $variant['activity'] !== $this->professionalActivity) {
            return;
        }

        $selected = $this->toggle($this->professionalVariants, $id);

        if ($selected === []) {
            return;
        }

        $this->professionalVariants = $selected;
        $this->professionalVariant = $selected[0];
        $this->prepareEditedPreview();
    }

    public function toggleEditedImprovement(string $id): void
    {
        $this->extraImprovements = $this->toggle($this->extraImprovements, $id);
        $this->prepareEditedPreview();
    }

    public function saveWorkspaceEdits(): void
    {
        if ($this->workspacePreview === null) {
            return;
        }

        $previous = $this->workspacePreview;
        $updated = $this->builder()->build(
            $this->businessType ?? 'winery',
            $this->extraImprovements,
            $this->websiteUrl ?: null,
            $this->professionalVariants,
            $this->objectives,
            $this->tools,
            $this->discoveredFacts,
        );
        $updated['id'] = $previous['id'];
        $updated['created_at'] = $previous['created_at'];
        $updated['business_name'] = trim($this->workspaceName) ?: $updated['business_name'];
        $updated['updated_at'] = now()->toIso8601String();

        $this->workspacePreview = $this->registry()->save($updated);
        $this->previewNotice = 'Tu Workspace ha evolucionado.';
        $this->refreshWorkspaces();
        $this->step = 'representations';
    }

    public function startNewWorkspace(): void
    {
        $this->reset([
            'activities',
            'businessType',
            'professionalActivity',
            'professionalVariant',
            'professionalVariants',
            'extraImprovements',
            'objectives',
            'tools',
            'hasWebsite',
            'websiteUrl',
            'workspaceName',
            'sequence',
            'workspacePreview',
            'selectedImprovementId',
            'newImprovementId',
            'evolvedAreaId',
            'discoveryMission',
            'discoveredFacts',
            'previewNotice',
        ]);
        $this->step = 'activity';
    }

    public function switchWorkspace(string $id): void
    {
        $workspace = $this->registry()->activate($id);

        if ($workspace === null) {
            return;
        }

        $this->loadWorkspace($workspace);
        $this->refreshWorkspaces();
        $this->step = 'overview';
    }

    public function chooseWebsite(bool $hasWebsite): void
    {
        $this->hasWebsite = $hasWebsite;

        if ($hasWebsite) {
            $this->step = 'url';

            return;
        }

        $this->preparePreview();
        $this->step = 'confirmation';
    }

    public function discoverWebsite(): void
    {
        $this->validate(['websiteUrl' => ['required', 'url']]);
        $this->discoveryMission = $this->missionRuntime()->detect(
            'Descubrir negocio desde la web',
            $this->businessType ?? 'winery',
            ['website' => $this->websiteUrl],
        );
        $this->sequence = 0;
        $this->step = 'discovery';
    }

    public function chooseImport(string $mode): void
    {
        $this->importMode = $mode;
        $this->preparePreview();
        $this->step = 'confirmation';
    }

    public function createWorkspace(): void
    {
        $this->sequence = 0;
        $this->step = 'building';
    }

    public function openNova(): mixed
    {
        if ($this->workspacePreview !== null) {
            $this->registry()->save($this->workspacePreview);
        }

        session()->flash('nova.workspace_transition', [
            'active' => true,
            'evolved_area_id' => $this->evolvedAreaId,
            'new_improvement_id' => $this->newImprovementId,
        ]);

        return redirect()->route('nova.nova-workspace');
    }

    public function render(): View
    {
        $catalog = app(CapabilityCatalog::class);
        $dataSources = app(DataSourceCatalog::class);
        $activeImprovement = $this->selectedImprovementId === null
            ? null
            : $this->evolution()->improvement($this->selectedImprovementId);
        $activeArea = $activeImprovement === null
            ? null
            : collect($this->workspacePreview['navigation'] ?? [])->firstWhere('id', $activeImprovement['area']);

        return view('livewire.nova.nova-studio', [
            'activityOptions' => $catalog->activities(),
            'businessOptions' => $catalog->businessTypes(),
            'objectiveOptions' => $catalog->objectives(),
            'toolOptions' => $dataSources->tools(),
            'professionalActivities' => $catalog->professionalActivities(),
            'professionalVariantOptions' => $this->professionalActivity === null
                ? []
                : $catalog->professionalVariants($this->professionalActivity),
            'improvementOptions' => $catalog->improvements(),
            'recommendations' => $this->workspacePreview === null
                ? []
                : $this->evolution()->recommendations($this->workspacePreview),
            'activeImprovement' => $activeImprovement,
            'activeArea' => $activeArea,
        ])->layout('layouts.studio');
    }

    /** @param array<int, string> $values
     * @return array<int, string>
     */
    private function toggle(array $values, string $id): array
    {
        if (in_array($id, $values, true)) {
            return array_values(array_diff($values, [$id]));
        }

        return [...$values, $id];
    }

    private function finishThinking(): void
    {
        $this->preparePreview();
        $this->step = 'proposal';
    }

    private function finishBuilding(): void
    {
        $this->preparePreview();
        $this->newImprovementId = $this->extraImprovements[array_key_last($this->extraImprovements)] ?? null;
        $improvement = $this->newImprovementId === null
            ? null
            : $this->evolution()->improvement($this->newImprovementId);
        $this->evolvedAreaId = $improvement['area'] ?? null;
        $this->workspacePreview = $this->registry()->save($this->workspacePreview ?? []);
        $this->loadWorkspace($this->workspacePreview);
        $this->refreshWorkspaces();
        $this->step = 'overview';
    }

    private function preparePreview(): void
    {
        $objectiveCapabilities = $this->catalog()->capabilitiesForObjectives($this->objectives);

        $this->workspacePreview = $this->builder()->build(
            $this->businessType ?? 'winery',
            array_values(array_unique([...$this->extraImprovements, ...$objectiveCapabilities])),
            $this->websiteUrl ?: null,
            $this->professionalVariants,
            $this->objectives,
            $this->tools,
            $this->discoveredFacts,
        );
        $this->workspaceName = (string) $this->workspacePreview['business_name'];
    }

    private function prepareEditedPreview(): void
    {
        if ($this->workspacePreview === null) {
            return;
        }

        $current = $this->workspacePreview;
        $preview = $this->builder()->build(
            $this->businessType ?? 'winery',
            $this->extraImprovements,
            $this->websiteUrl ?: null,
            $this->professionalVariants,
            $this->objectives,
            $this->tools,
            $this->discoveredFacts,
        );
        $preview['id'] = $current['id'];
        $preview['created_at'] = $current['created_at'];
        $preview['business_name'] = trim($this->workspaceName) ?: $preview['business_name'];
        $this->workspacePreview = $preview;
        $this->extraImprovements = $preview['improvement_ids'];
    }

    private function completeEvolution(): void
    {
        if ($this->workspacePreview === null || $this->selectedImprovementId === null) {
            $this->step = 'overview';

            return;
        }

        $improvement = $this->evolution()->improvement($this->selectedImprovementId);
        $this->workspacePreview = $this->evolution()->improve(
            $this->workspacePreview,
            $this->selectedImprovementId,
        );
        $this->newImprovementId = $this->selectedImprovementId;
        $this->evolvedAreaId = $improvement['area'] ?? null;
        $this->extraImprovements = $this->workspacePreview['improvement_ids'] ?? [];
        $this->previewNotice = (string) ($improvement['result'] ?? 'Tu Workspace ha evolucionado.');
        $this->workspacePreview = $this->registry()->save($this->workspacePreview);
        $this->refreshWorkspaces();
        $this->step = 'representations';
    }

    /** @param array<string, mixed> $workspace */
    private function loadWorkspace(array $workspace): void
    {
        $rebuilt = $this->builder()->build(
            $workspace['business_type'] ?? 'winery',
            $workspace['improvement_ids'] ?? [],
            $workspace['website'] ?? null,
            $workspace['professional_variants'] ?? array_filter([$workspace['professional_variant'] ?? null]),
            $workspace['objectives'] ?? [],
            $workspace['tools'] ?? [],
            $workspace['discovered_facts'] ?? [],
        );
        $rebuilt['id'] = $workspace['id'] ?? $rebuilt['id'];
        $rebuilt['created_at'] = $workspace['created_at'] ?? $rebuilt['created_at'];
        $rebuilt['updated_at'] = $workspace['updated_at'] ?? $rebuilt['updated_at'];
        $rebuilt['business_name'] = $workspace['business_name'] ?? $rebuilt['business_name'];

        $this->workspacePreview = $rebuilt;
        $this->businessType = (string) ($this->workspacePreview['business_type'] ?? 'winery');
        $this->professionalActivity = $this->workspacePreview['professional_activity'] ?? null;
        $this->professionalVariants = $this->workspacePreview['professional_variants']
            ?? array_values(array_filter([$this->workspacePreview['professional_variant'] ?? null]));
        $this->professionalVariant = $this->professionalVariants[0] ?? null;
        $this->extraImprovements = $this->workspacePreview['improvement_ids'] ?? [];
        $this->objectives = $this->workspacePreview['objectives'] ?? [];
        $this->tools = $this->workspacePreview['tools'] ?? [];
        $this->websiteUrl = (string) ($this->workspacePreview['website'] ?? '');
        $this->discoveredFacts = (array) ($this->workspacePreview['discovered_facts'] ?? []);
        $this->workspaceName = (string) ($this->workspacePreview['business_name'] ?? '');
    }

    private function refreshWorkspaces(): void
    {
        $this->workspaces = $this->registry()->all();
    }

    private function catalog(): CapabilityCatalog
    {
        return app(CapabilityCatalog::class);
    }

    private function dataSources(): DataSourceCatalog
    {
        return app(DataSourceCatalog::class);
    }

    private function builder(): WorkspaceBuilder
    {
        return app(WorkspaceBuilder::class);
    }

    private function evolution(): WorkspaceEvolution
    {
        return app(WorkspaceEvolution::class);
    }

    private function missionRuntime(): MissionRuntime
    {
        return app(MissionRuntime::class);
    }

    private function registry(): WorkspaceRegistry
    {
        return app(WorkspaceRegistry::class);
    }
}
