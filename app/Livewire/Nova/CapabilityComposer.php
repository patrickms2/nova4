<?php

declare(strict_types=1);

namespace App\Livewire\Nova;

use App\Enums\Nova\NovaBindingTarget;
use App\Enums\Nova\NovaRepresentationType;
use App\Models\Nova\NovaBinding;
use App\Models\Nova\NovaCapability;
use App\Models\Nova\NovaGroup;
use App\Models\Nova\NovaPanel;
use App\Models\Nova\NovaTool;
use App\Models\Nova\NovaWorkspace;
use App\Support\Nova\NovaDefinitionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

final class CapabilityComposer extends Component
{
    public ?int $workspaceId = null;
    public ?int $panelId = null;
    public string $role = 'owner';
    public string $representation = 'livewire';
    public ?int $selectedGroupId = null;
    public ?int $selectedCapabilityId = null;
    public string $notice = '✓ Cambios guardados automáticamente';
    public string $capabilityLabel = '';
    public string $capabilityIcon = '';
    public bool $showAdvanced = false;

    public function mount(): void
    {
        $workspace = NovaWorkspace::query()->where('key', 'community')->first()
            ?? NovaWorkspace::query()->orderBy('name')->firstOrFail();

        $this->workspaceId = $workspace->id;
        $this->syncPanelAndGroup();
    }

    public function updatedWorkspaceId(): void
    {
        $this->syncPanelAndGroup();
    }

    public function updatedPanelId(): void
    {
        $this->syncGroup();
    }

    public function updatedRole(): void
    {
        $this->syncGroup();
    }

    public function updatedRepresentation(): void
    {
        $this->syncGroup();
    }

    public function selectGroup(int $groupId): void
    {
        $this->selectedGroupId = $groupId;
        $this->selectedCapabilityId = $this->groupBindings($groupId)->first()?->capability_id;
        $this->hydrateCapabilityEditor();
    }

    public function selectCapability(int $capabilityId): void
    {
        $this->selectedCapabilityId = $capabilityId;
        $this->hydrateCapabilityEditor();
    }

    public function toggleAdvanced(): void
    {
        $this->showAdvanced = ! $this->showAdvanced;
    }

    public function toggleCapability(int $bindingId): void
    {
        $binding = NovaBinding::query()->where('panel_id', $this->panel()->id)->findOrFail($bindingId);
        $binding->update(['visible' => ! $binding->visible]);
        $this->notice = '✓ Cambios guardados automáticamente';
    }

    public function toggleTool(int $toolId): void
    {
        $tool = NovaTool::query()->findOrFail($toolId);
        $binding = NovaBinding::query()
            ->where('panel_id', $this->panel()->id)
            ->where('tool_id', $tool->id)
            ->where('target_type', NovaBindingTarget::Tool)
            ->where('role', $this->role)
            ->where('representation', NovaRepresentationType::from($this->representation))
            ->first();

        if ($binding) {
            $binding->update(['visible' => ! $binding->visible]);
        } else {
            NovaBinding::query()->create([
                'panel_id' => $this->panel()->id,
                'group_id' => $this->selectedGroupId,
                'capability_id' => $tool->capability_id,
                'tool_id' => $tool->id,
                'target_type' => NovaBindingTarget::Tool,
                'role' => $this->role,
                'representation' => NovaRepresentationType::from($this->representation),
                'visible' => false,
                'sort' => $tool->sort,
                'settings' => [],
            ]);
        }

        $this->notice = '✓ Herramienta guardada automáticamente';
    }

    public function saveCapabilityPresentation(): void
    {
        $this->validate([
            'capabilityLabel' => ['required', 'string', 'max:80'],
            'capabilityIcon' => ['nullable', 'string', 'max:120'],
        ]);

        $binding = $this->selectedBinding();
        if (! $binding) {
            return;
        }

        $settings = $binding->settings ?? [];
        $settings['label'] = $this->capabilityLabel;
        $settings['icon'] = $this->capabilityIcon ?: null;
        $binding->update(['settings' => $settings]);

        $this->notice = '✓ Presentación guardada automáticamente';
    }

    /** @param array<int, int|string> $orderedIds */
    public function reorderGroups(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $groupId) {
                NovaGroup::query()
                    ->where('panel_id', $this->panel()->id)
                    ->whereKey((int) $groupId)
                    ->update(['sort' => ($index + 1) * 10]);
            }
        });

        $this->notice = '✓ Orden de grupos guardado';
    }

    /** @param array<int, int|string> $orderedBindingIds */
    public function reorderCapabilities(int $groupId, array $orderedBindingIds): void
    {
        DB::transaction(function () use ($groupId, $orderedBindingIds): void {
            foreach ($orderedBindingIds as $index => $bindingId) {
                NovaBinding::query()
                    ->where('panel_id', $this->panel()->id)
                    ->whereKey((int) $bindingId)
                    ->update(['group_id' => $groupId, 'sort' => ($index + 1) * 10]);
            }
        });

        $this->notice = '✓ Orden guardado';
    }

    public function exportWorkspace(NovaDefinitionService $definition): void
    {
        $path = $definition->exportWorkspaceToStorage((int) $this->workspaceId);
        $this->notice = 'Exportado workspace: '.basename($path);
    }

    public function exportAll(NovaDefinitionService $definition): void
    {
        $path = $definition->exportAllToStorage();
        $this->notice = 'NOVA Definition completa exportada: '.basename($path);
    }

    public function render(): View
    {
        $workspaces = NovaWorkspace::query()->orderBy('name')->get();
        $panels = NovaPanel::query()->where('workspace_id', $this->workspaceId)->orderBy('sort')->get();
        $groups = $this->panel()->groups()->orderBy('sort')->get();
        $selectedGroup = $groups->firstWhere('id', $this->selectedGroupId);
        $bindings = $this->groupBindings($selectedGroup?->id);
        $selectedCapability = $this->selectedCapabilityId
            ? NovaCapability::query()->with(['tools', 'resources', 'connectors'])->find($this->selectedCapabilityId)
            : null;

        return view('livewire.nova.capability-composer', [
            'workspaces' => $workspaces,
            'panels' => $panels,
            'panel' => $this->panel(),
            'groups' => $groups,
            'selectedGroup' => $selectedGroup,
            'bindings' => $bindings,
            'selectedCapability' => $selectedCapability,
            'previewItems' => $this->previewItems(),
        ])->layout('layouts.studio');
    }

    private function syncPanelAndGroup(): void
    {
        $panel = NovaPanel::query()
            ->where('workspace_id', $this->workspaceId)
            ->orderBy('sort')
            ->firstOrFail();

        $this->panelId = $panel->id;
        $this->syncGroup();
    }

    private function syncGroup(): void
    {
        $group = $this->panel()->groups()->orderBy('sort')->first();
        $this->selectedGroupId = $group?->id;
        $this->selectedCapabilityId = $this->groupBindings($group?->id)->first()?->capability_id;
        $this->hydrateCapabilityEditor();
    }

    private function panel(): NovaPanel
    {
        return NovaPanel::query()
            ->where('workspace_id', $this->workspaceId)
            ->findOrFail($this->panelId);
    }

    private function groupBindings(?int $groupId)
    {
        if (! $groupId) {
            return collect();
        }

        return NovaBinding::query()
            ->with('capability')
            ->where('panel_id', $this->panel()->id)
            ->where('group_id', $groupId)
            ->where('target_type', NovaBindingTarget::Capability)
            ->where('role', $this->role)
            ->where('representation', NovaRepresentationType::from($this->representation))
            ->orderBy('sort')
            ->get();
    }

    private function previewItems()
    {
        return NovaBinding::query()
            ->with(['group', 'capability'])
            ->where('panel_id', $this->panel()->id)
            ->where('target_type', NovaBindingTarget::Capability)
            ->where('role', $this->role)
            ->where('representation', NovaRepresentationType::from($this->representation))
            ->where('visible', true)
            ->orderBy('sort')
            ->get()
            ->groupBy(fn (NovaBinding $binding): string => $binding->group?->name ?? 'General');
    }

    private function selectedBinding(): ?NovaBinding
    {
        if (! $this->selectedCapabilityId) {
            return null;
        }

        return NovaBinding::query()
            ->where('panel_id', $this->panel()->id)
            ->where('capability_id', $this->selectedCapabilityId)
            ->where('target_type', NovaBindingTarget::Capability)
            ->where('role', $this->role)
            ->where('representation', NovaRepresentationType::from($this->representation))
            ->first();
    }

    private function hydrateCapabilityEditor(): void
    {
        $capability = $this->selectedCapabilityId
            ? NovaCapability::query()->find($this->selectedCapabilityId)
            : null;

        if (! $capability) {
            $this->capabilityLabel = '';
            $this->capabilityIcon = '';
            return;
        }

        $binding = $this->selectedBinding();
        $this->capabilityLabel = (string) ($binding?->settings['label'] ?? $capability->name);
        $this->capabilityIcon = (string) ($binding?->settings['icon'] ?? '');
    }
}
