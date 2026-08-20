<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuditTaxistaDuplicatesCommand extends Command
{
    protected $signature = 'users:audit-taxista-duplicates
        {--limit=100 : Maximum number of duplicate groups to display}';

    protected $description = 'Audit duplicate taxista users by nif, licencia, and name + municipio';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $duplicateGroups = collect([
            [
                'label' => 'Duplicados por NIF',
                'groups' => $this->getDuplicateGroups('nif', fn ($query) => $query
                    ->whereNotNull('nif')
                    ->where('nif', '!=', '')),
            ],
            [
                'label' => 'Duplicados por licencia',
                'groups' => $this->getDuplicateGroups('licencia', fn ($query) => $query
                    ->whereNotNull('licencia')
                    ->where('licencia', '!=', '')),
            ],
            [
                'label' => 'Duplicados por nombre y municipio',
                'groups' => $this->getNameMunicipioDuplicateGroups(),
            ],
        ]);

        foreach ($duplicateGroups as $groupSet) {
            $this->newLine();
            $this->info($groupSet['label']);

            /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $groups */
            $groups = $groupSet['groups'];

            if ($groups->isEmpty()) {
                $this->line('Sin duplicados detectados.');
                continue;
            }

            $rows = [];

            foreach ($groups->take($limit) as $group) {
                $rows[] = [
                    $group['key'],
                    implode(', ', $group['user_ids']),
                    implode(' | ', $group['names']),
                    implode(', ', $group['municipios']),
                    $group['count'],
                ];
            }

            $this->table(
                ['Clave', 'User IDs', 'Nombres', 'Municipios', 'Cantidad'],
                $rows,
            );
        }

        return self::SUCCESS;
    }

    /**
     * @param  callable(\Illuminate\Database\Eloquent\Builder<User>): \Illuminate\Database\Eloquent\Builder<User>  $constraint
     * @return Collection<int, array{key:string, user_ids:array<int, string>, names:array<int, string>, municipios:array<int, string>, count:int}>
     */
    protected function getDuplicateGroups(string $column, callable $constraint): Collection
    {
        $duplicateValues = User::query()
            ->where('role', 'taxista')
            ->tap($constraint)
            ->select($column, DB::raw('COUNT(*) as aggregate_count'))
            ->groupBy($column)
            ->having('aggregate_count', '>', 1)
            ->pluck($column)
            ->filter()
            ->values();

        return $duplicateValues
            ->map(function (string $value) use ($column): array {
                $users = User::query()
                    ->with('municipio:id,nombre')
                    ->where('role', 'taxista')
                    ->where($column, $value)
                    ->orderBy('id')
                    ->get(['id', 'name', 'municipio_id', $column]);

                return [
                    'key' => $value,
                    'user_ids' => $users->pluck('id')->map(fn ($id): string => (string) $id)->all(),
                    'names' => $users->pluck('name')->filter()->unique()->values()->all(),
                    'municipios' => $users->map(fn (User $user): string => (string) ($user->municipio?->nombre ?? '-'))->unique()->values()->all(),
                    'count' => $users->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    /**
     * @return Collection<int, array{key:string, user_ids:array<int, string>, names:array<int, string>, municipios:array<int, string>, count:int}>
     */
    protected function getNameMunicipioDuplicateGroups(): Collection
    {
        $duplicates = User::query()
            ->where('role', 'taxista')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereNotNull('municipio_id')
            ->select('name', 'municipio_id', DB::raw('COUNT(*) as aggregate_count'))
            ->groupBy('name', 'municipio_id')
            ->having('aggregate_count', '>', 1)
            ->get();

        return $duplicates
            ->map(function (object $duplicate): array {
                $users = User::query()
                    ->with('municipio:id,nombre')
                    ->where('role', 'taxista')
                    ->where('name', $duplicate->name)
                    ->where('municipio_id', $duplicate->municipio_id)
                    ->orderBy('id')
                    ->get(['id', 'name', 'municipio_id', 'licencia', 'nif']);

                $municipios = $users
                    ->map(fn (User $user): string => (string) ($user->municipio?->nombre ?? '-'))
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'key' => sprintf('%s / %s', (string) $duplicate->name, implode(', ', $municipios)),
                    'user_ids' => $users->pluck('id')->map(fn ($id): string => (string) $id)->all(),
                    'names' => $users->pluck('name')->filter()->unique()->values()->all(),
                    'municipios' => $municipios,
                    'count' => $users->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();
    }
}
