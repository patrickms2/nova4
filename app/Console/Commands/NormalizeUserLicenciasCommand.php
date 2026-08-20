<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NormalizeUserLicenciasCommand extends Command
{
    protected $signature = 'users:normalize-licencias
        {--apply : Persist the normalized licenses in users.licencia}
        {--limit=100 : Number of preview rows to show}';

    protected $description = 'Preview or normalize user licenses to the LM 00 MUNICIPIO format';

    /**
     * @var array<string, string>
     */
    protected array $municipioNameMap = [
        'TIAS' => 'TIAS',
        'TEGUISE' => 'TEGUISE',
        'YAIZA' => 'YAIZA',
        'TINAJO' => 'TINAJO',
    ];

    public function handle(): int
    {
        $users = User::query()
            ->with(['municipio:id,nombre'])
            ->whereNotNull('licencia')
            ->where('licencia', '!=', '')
            ->orderBy('id')
            ->get(['id', 'name', 'licencia', 'municipio_id']);

        if ($users->isEmpty()) {
            $this->info('No hay usuarios con licencia para normalizar.');

            return self::SUCCESS;
        }

        $previewRows = [];
        $updates = [];
        $skippedCount = 0;

        /** @var User $user */
        foreach ($users as $user) {
            $normalizedLicense = $this->normalizeLicense($user);

            if ($normalizedLicense === null) {
                $skippedCount++;
                continue;
            }

            if ($normalizedLicense === $user->licencia) {
                continue;
            }

            $updates[$user->id] = $normalizedLicense;

            if (count($previewRows) < (int) $this->option('limit')) {
                $previewRows[] = [
                    $user->id,
                    $user->name,
                    (string) $user->licencia,
                    (string) ($user->municipio?->nombre ?? '-'),
                    $normalizedLicense,
                ];
            }
        }

        $this->info(sprintf('Analizados: %d', $users->count()));
        $this->line(sprintf('Normalizables: %d', count($updates)));
        $this->line(sprintf('Omitidos: %d', $skippedCount));

        if ($previewRows !== []) {
            $this->newLine();
            $this->table(
                ['ID', 'Nombre', 'Licencia actual', 'Municipio', 'Licencia normalizada'],
                $previewRows,
            );
        }

        if (! $this->option('apply')) {
            $this->newLine();
            $this->comment('Vista previa solamente. Usa --apply para guardar los cambios.');

            return self::SUCCESS;
        }

        if ($updates === []) {
            $this->info('No hay cambios para aplicar.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($updates): void {
            foreach ($updates as $userId => $normalizedLicense) {
                User::query()
                    ->whereKey($userId)
                    ->update(['licencia' => $normalizedLicense]);
            }
        });

        $this->info(sprintf('Se actualizaron %d licencias.', count($updates)));

        return self::SUCCESS;
    }

    protected function normalizeLicense(User $user): ?string
    {
        $licenseNumber = $this->extractLicenseNumber((string) $user->licencia);

        if ($licenseNumber === null) {
            return null;
        }

        $municipioName = $this->resolveMunicipioName($user);

        if ($municipioName === null) {
            return null;
        }

        return sprintf('LM %02d %s', $licenseNumber, $municipioName);
    }

    protected function extractLicenseNumber(string $license): ?int
    {
        $trimmedLicense = trim($license);

        if (preg_match('/^LM\s*(\d+)/iu', $trimmedLicense, $matches) === 1) {
            return (int) $matches[1];
        }

        preg_match_all('/\d+/', $trimmedLicense, $matches);

        if (($matches[0] ?? []) === []) {
            return null;
        }

        return (int) end($matches[0]);
    }

    protected function resolveMunicipioName(User $user): ?string
    {
        $municipioName = trim((string) ($user->municipio?->nombre ?? ''));

        if ($municipioName === '') {
            return null;
        }

        $normalizedMunicipioName = Str::of($municipioName)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z]/', '')
            ->value();

        return $this->municipioNameMap[$normalizedMunicipioName] ?? null;
    }
}
