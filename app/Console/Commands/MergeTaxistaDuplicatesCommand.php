<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MergeTaxistaDuplicatesCommand extends Command
{
    protected $signature = 'users:merge-taxista-duplicates
        {--apply : Apply the merge and deactivate duplicate records}
        {--limit=100 : Maximum duplicate groups to display}';

    protected $description = 'Merge duplicate taxista users keeping the imported PDF record as the valid one';

    public function handle(): int
    {
        $groups = $this->buildDuplicateGroups();

        if ($groups->isEmpty()) {
            $this->info('No se detectaron duplicados fusionables por municipio + licencia.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($groups->take((int) $this->option('limit')) as $group) {
            $rows[] = [
                $group['group_key'],
                $group['keeper']->id,
                implode(', ', array_map(fn (User $user): string => (string) $user->id, $group['duplicates'])),
                $group['preferred_license'],
                $group['municipio'],
                count($group['duplicates']) + 1,
            ];
        }

        $this->table(
            ['Grupo', 'Keeper', 'Duplicados', 'Licencia final', 'Municipio', 'Cantidad'],
            $rows,
        );

        if (! $this->option('apply')) {
            $this->newLine();
            $this->comment('Vista previa solamente. Usa --apply para fusionar y desactivar duplicados antiguos.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($groups): void {
            foreach ($groups as $group) {
                $this->mergeGroup($group);
            }
        });

        $this->info(sprintf('Fusión aplicada. Grupos procesados: %d', $groups->count()));

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, array{
     *     group_key:string,
     *     municipio:string,
     *     preferred_license:string,
     *     keeper:User,
     *     duplicates:array<int, User>,
     *     members:Collection<int, User>
     * }>
     */
    protected function buildDuplicateGroups(): Collection
    {
        $users = User::query()
            ->with('municipio:id,nombre')
            ->where('role', 'taxista')
            ->whereNotNull('municipio_id')
            ->whereNotNull('licencia')
            ->where('licencia', '!=', '')
            ->orderBy('municipio_id')
            ->orderBy('id')
            ->get();

        return $users
            ->map(function (User $user): ?array {
                $licenseNumber = $this->extractLicenseNumber($user->licencia);

                if ($licenseNumber === null) {
                    return null;
                }

                return [
                    'key' => sprintf('%s:%02d', (string) $user->municipio_id, $licenseNumber),
                    'user' => $user,
                ];
            })
            ->filter()
            ->groupBy('key')
            ->map(function (Collection $items, string $groupKey): ?array {
                $members = $items
                    ->pluck('user')
                    ->filter(fn ($user): bool => $user instanceof User)
                    ->values();

                if ($members->count() < 2) {
                    return null;
                }

                /** @var User $keeper */
                $keeper = $members
                    ->sortBy([
                        fn (User $user): int => -$this->calculateKeeperScore($user),
                        fn (User $user): int => $user->id,
                    ])
                    ->first();

                $duplicates = $members
                    ->reject(fn (User $user): bool => $user->is($keeper))
                    ->values()
                    ->all();

                if ($duplicates === []) {
                    return null;
                }

                return [
                    'group_key' => $groupKey,
                    'municipio' => (string) ($keeper->municipio?->nombre ?? '-'),
                    'preferred_license' => $this->resolvePreferredLicense($members),
                    'keeper' => $keeper,
                    'duplicates' => $duplicates,
                    'members' => $members,
                ];
            })
            ->filter()
            ->values();
    }

    protected function mergeGroup(array $group): void
    {
        /** @var User $keeper */
        $keeper = $group['keeper'];
        /** @var Collection<int, User> $members */
        $members = $group['members'];

        $keeper->update($this->buildMergedPayload($keeper, $members, (string) $group['preferred_license']));

        /** @var User $duplicate */
        foreach ($group['duplicates'] as $duplicate) {
            $this->reassignRelations($duplicate->id, $keeper->id);
            $duplicate->update([
                'status' => false,
                'role' => 'taxista_old',
            ]);
        }
    }

    protected function reassignRelations(int $fromUserId, int $toUserId): void
    {
        DB::table('taxista_taxis')
            ->where('taxista_user_id', $fromUserId)
            ->update(['taxista_user_id' => $toUserId]);

        DB::table('users')
            ->where('role', 'conductor')
            ->where('taxista_id', $fromUserId)
            ->update(['taxista_id' => $toUserId]);
    }

    /**
     * @param  Collection<int, User>  $members
     * @return array<string, mixed>
     */
    protected function buildMergedPayload(User $keeper, Collection $members, string $preferredLicense): array
    {
        return [
            'status' => true,
            'role' => 'taxista',
            'name' => $keeper->name,
            'nif' => $keeper->nif,
            'licencia' => $preferredLicense,
        ];
    }

    /**
     * @param  Collection<int, User>  $members
     */
    protected function resolvePreferredLicense(Collection $members): string
    {
        $formattedLicense = $members
            ->pluck('licencia')
            ->filter(fn ($license): bool => $this->isFormattedLicense((string) $license))
            ->sortByDesc(fn ($license): int => $this->isImportedPdfLicense((string) $license) ? 1 : 0)
            ->first();

        if ($formattedLicense) {
            $licenseNumber = $this->extractLicenseNumber((string) $formattedLicense);
            $municipio = $this->extractLicenseMunicipio((string) $formattedLicense);

            if ($licenseNumber !== null && $municipio !== null) {
                return sprintf('LM %02d %s', $licenseNumber, $municipio);
            }

            return (string) $formattedLicense;
        }

        /** @var User|null $firstMember */
        $firstMember = $members->first();
        $licenseNumber = $this->extractLicenseNumber((string) $firstMember?->licencia);
        $municipio = $firstMember?->municipio?->nombre;

        if ($licenseNumber !== null && filled($municipio)) {
            return sprintf('LM %02d %s', $licenseNumber, Str::of((string) $municipio)->ascii()->upper()->replaceMatches('/[^A-Z]/', '')->value());
        }

        return (string) $firstMember?->licencia;
    }

    protected function calculateKeeperScore(User $user): int
    {
        $score = 0;

        $score += $this->isImportedPdfLicense((string) $user->licencia) ? 10000 : 0;
        $score += DB::table('taxista_taxis')->where('taxista_user_id', $user->id)->count() * 1000;
        $score += DB::table('users')->where('role', 'conductor')->where('taxista_id', $user->id)->count() * 500;
        $score += filled($user->nif) ? 20 : 0;
        $score += $this->isFormattedLicense((string) $user->licencia) ? 10 : 0;
        $score += filled($user->name) ? min(strlen((string) $user->name), 50) : 0;

        return $score;
    }

    protected function extractLicenseNumber(?string $license): ?int
    {
        if (! filled($license)) {
            return null;
        }

        preg_match_all('/\d+/', (string) $license, $matches);

        if (($matches[0] ?? []) === []) {
            return null;
        }

        return (int) ltrim((string) end($matches[0]), '0') ?: (int) end($matches[0]);
    }

    protected function isFormattedLicense(string $license): bool
    {
        return Str::startsWith(Str::upper(trim($license)), 'LM ');
    }

    protected function isImportedPdfLicense(string $license): bool
    {
        return preg_match('/^LM\s+\d+\s+[A-ZÁÉÍÓÚÜÑ]+$/u', trim($license)) === 1;
    }

    protected function extractLicenseMunicipio(string $license): ?string
    {
        if (preg_match('/^LM\s+\d+\s+([A-ZÁÉÍÓÚÜÑ]+)$/u', trim($license), $matches) !== 1) {
            return null;
        }

        return Str::of($matches[1])
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z]/', '')
            ->value();
    }
}
