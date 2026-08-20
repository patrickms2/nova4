<?php

declare(strict_types=1);

namespace App\Domain\Nova\Studio\Workspace;

use Illuminate\Support\Str;

final readonly class WorkspaceRegistry
{
    public function __construct(private WorkspaceEvolution $evolution) {}

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $workspaces = session('nova.workspaces', []);

        if (! is_array($workspaces) || $workspaces === []) {
            $legacyWorkspace = session('nova.workspace');

            if (is_array($legacyWorkspace)) {
                $workspace = $this->prepare($legacyWorkspace);
                $this->persist([$workspace], $workspace['id']);

                return [$workspace];
            }

            return [];
        }

        $workspaces = array_values(array_map(
            fn (array $workspace): array => $this->prepare($workspace),
            array_filter($workspaces, 'is_array'),
        ));

        if ($workspaces !== []) {
            $activeId = session('nova.active_workspace_id', $workspaces[0]['id']);
            $this->persist($workspaces, (string) $activeId);
        }

        return $workspaces;
    }

    /** @return array<string, mixed>|null */
    public function active(): ?array
    {
        $workspaces = $this->all();

        if ($workspaces === []) {
            return null;
        }

        $activeId = (string) session('nova.active_workspace_id', $workspaces[0]['id']);

        return collect($workspaces)->firstWhere('id', $activeId) ?? $workspaces[0];
    }

    /** @param array<string, mixed> $workspace
     * @return array<string, mixed>
     */
    public function save(array $workspace): array
    {
        $existing = isset($workspace['id'])
            ? collect($this->all())->firstWhere('id', $workspace['id'])
            : null;

        foreach (['custom_actions', 'custom_relations'] as $key) {
            if (! array_key_exists($key, $workspace) && is_array($existing) && isset($existing[$key])) {
                $workspace[$key] = $existing[$key];
            }
        }

        $workspace = $this->prepare($workspace);
        $workspaces = $this->all();
        $replaced = false;

        foreach ($workspaces as $index => $storedWorkspace) {
            if ($storedWorkspace['id'] !== $workspace['id']) {
                continue;
            }

            $workspaces[$index] = $workspace;
            $replaced = true;
            break;
        }

        if (! $replaced) {
            $workspaces[] = $workspace;
        }

        $this->persist($workspaces, $workspace['id']);

        return $workspace;
    }

    /** @return array<string, mixed>|null */
    public function activate(string $id): ?array
    {
        $workspaces = $this->all();
        $workspace = collect($workspaces)->firstWhere('id', $id);

        if (! is_array($workspace)) {
            return null;
        }

        $this->persist($workspaces, $id);

        return $workspace;
    }

    /** @param array<string, mixed> $workspace
     * @return array<string, mixed>
     */
    private function prepare(array $workspace): array
    {
        $workspace = $this->evolution->normalize($workspace);

        return [
            ...$workspace,
            'id' => (string) ($workspace['id'] ?? Str::uuid()),
            'updated_at' => (string) ($workspace['updated_at'] ?? $workspace['created_at'] ?? now()->toIso8601String()),
        ];
    }

    /** @param array<int, array<string, mixed>> $workspaces */
    private function persist(array $workspaces, string $activeId): void
    {
        $active = collect($workspaces)->firstWhere('id', $activeId) ?? $workspaces[0] ?? null;

        session()->put('nova.workspaces', array_values($workspaces));
        session()->put('nova.active_workspace_id', $active['id'] ?? null);
        session()->put('nova.workspace', $active);
    }
}
