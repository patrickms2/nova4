<?php

namespace App\Console\Commands;

use App\Models\Taxi\Municipio;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class ImportTaxistasFromPdfCommand extends Command
{
    protected $signature = 'users:import-taxistas-pdf
        {--apply : Persist the parsed taxistas into users}
        {--file=* : PDF files to parse}
        {--limit=50 : Number of preview rows to display}';

    protected $description = 'Parse taxista PDFs and create or update users with role taxista';

    /**
     * @var array<string, string>
     */
    protected array $defaultFiles = [
        'public/docs/patric,teguise (2).pdf',
        'public/docs/patric. tias.pdf',
        'public/docs/patric.tinajo (2).pdf',
        'public/docs/patrick yaiza.pdf',
    ];

    /**
     * @var array<string, string>
     */
    protected array $municipioAliases = [
        'TEGUISE' => 'TEGUISE',
        'TIAS' => 'TIAS',
        'TIAS' => 'TIAS',
        'TINAJO' => 'TINAJO',
        'YAIZA' => 'YAIZA',
    ];

    public function handle(): int
    {
        $files = $this->resolveFiles();

        if ($files === []) {
            $this->error('No se encontraron PDFs para procesar.');

            return self::FAILURE;
        }

        $municipios = Municipio::query()
            ->get(['id', 'nombre'])
            ->mapWithKeys(fn (Municipio $municipio): array => [
                $this->normalizeMunicipioName((string) $municipio->nombre) => $municipio,
            ]);

        $records = [];

        foreach ($files as $file) {
            foreach ($this->parsePdf($file) as $record) {
                $normalizedMunicipio = $this->normalizeMunicipioName($record['municipio']);
                $municipio = $municipios->get($normalizedMunicipio);

                $records[] = [
                    ...$record,
                    'municipio_id' => $municipio?->id,
                    'municipio_db' => $municipio?->nombre,
                ];
            }
        }

        if ($records === []) {
            $this->warn('No se detectaron taxistas en los PDFs.');

            return self::SUCCESS;
        }

        $previewRows = [];
        $operations = [];
        $skipped = 0;

        foreach ($records as $record) {
            if (! $record['municipio_id']) {
                $skipped++;
                continue;
            }

            $matchedUser = $this->findMatchingUser($record);
            $payload = $this->buildUserPayload($record, $matchedUser);

            $operations[] = [
                'user' => $matchedUser,
                'payload' => $payload,
                'record' => $record,
                'action' => $matchedUser ? 'update' : 'create',
            ];

            if (count($previewRows) < (int) $this->option('limit')) {
                $previewRows[] = [
                    $matchedUser?->id ?? '-',
                    $matchedUser ? 'update' : 'create',
                    $record['licencia'],
                    $record['name'],
                    $record['nif'] ?? '-',
                    $record['municipio_db'] ?? $record['municipio'],
                ];
            }
        }

        $this->info(sprintf('PDFs procesados: %d', count($files)));
        $this->line(sprintf('Registros detectados: %d', count($records)));
        $this->line(sprintf('Operaciones preparadas: %d', count($operations)));
        $this->line(sprintf('Omitidos por municipio no resuelto: %d', $skipped));

        if ($previewRows !== []) {
            $this->newLine();
            $this->table(
                ['User ID', 'Acción', 'Licencia', 'Nombre', 'NIF', 'Municipio'],
                $previewRows,
            );
        }

        if (! $this->option('apply')) {
            $this->newLine();
            $this->comment('Vista previa solamente. Usa --apply para crear o actualizar users.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($operations): void {
            foreach ($operations as $operation) {
                /** @var User|null $user */
                $user = $operation['user'];
                /** @var array<string, mixed> $payload */
                $payload = $operation['payload'];

                if ($user) {
                    $user->update($payload);
                    continue;
                }

                User::query()->create($payload);
            }
        });

        $this->info(sprintf('Importación aplicada. Registros procesados: %d', count($operations)));

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function resolveFiles(): array
    {
        $providedFiles = collect((array) $this->option('file'))
            ->filter()
            ->map(fn (string $file): string => str_starts_with($file, '/')
                ? $file
                : base_path($file))
            ->values()
            ->all();

        if ($providedFiles !== []) {
            return array_values(array_filter($providedFiles, 'is_file'));
        }

        return array_values(array_filter(
            array_map(fn (string $file): string => base_path($file), $this->defaultFiles),
            'is_file',
        ));
    }

    /**
     * @return array<int, array{name:string, licencia:string, nif:?string, municipio:string}>
     */
    protected function parsePdf(string $file): array
    {
        $process = new Process(['pdftotext', $file, '-']);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(sprintf('No se pudo extraer texto de %s', $file));
        }

        $lines = preg_split('/\R/u', $process->getOutput()) ?: [];
        $records = [];

        for ($index = 0; $index < count($lines); $index++) {
            $currentLine = trim((string) $lines[$index]);

            if (! preg_match('/^(LM\s*\d+\s+)([A-ZÁÉÍÓÚÜÑ]+)$/u', $currentLine, $matches)) {
                continue;
            }

            $licencia = $this->formatLicense(
                $this->extractLicenseNumber($currentLine),
                $matches[2],
            );
            $municipio = $matches[2];
            $name = $this->extractNameFromBlock($lines, $index + 1);
            $nif = $this->extractNifFromBlock($lines, $index + 1);

            if (! $name || $licencia === null) {
                continue;
            }

            $records[] = [
                'name' => $name,
                'licencia' => $licencia,
                'nif' => $nif,
                'municipio' => $municipio,
            ];
        }

        return $this->deduplicateRecords($records);
    }

    protected function extractNameFromBlock(array $lines, int $startIndex): ?string
    {
        for ($cursor = $startIndex; $cursor < min(count($lines), $startIndex + 12); $cursor++) {
            $candidate = preg_replace('/\s+/u', ' ', trim((string) $lines[$cursor]));

            if (! filled($candidate)) {
                continue;
            }

            if ($this->isIgnoredBlockLine($candidate)) {
                continue;
            }

            if (! preg_match('/^[A-ZÁÉÍÓÚÜÑ ]+$/u', $candidate)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    protected function extractNifFromBlock(array $lines, int $startIndex): ?string
    {
        for ($cursor = $startIndex; $cursor < min(count($lines), $startIndex + 12); $cursor++) {
            $candidate = preg_replace('/\s+/u', ' ', trim((string) $lines[$cursor]));

            if (! filled($candidate)) {
                continue;
            }

            if (preg_match('/(?:D\.?N\.?I\.?|DNI)\s*[:.]?\s*([0-9]{8}[A-Z])/iu', $candidate, $matches)) {
                return strtoupper($matches[1]);
            }

            if (preg_match('/^[0-9]{8}[A-Z]$/i', $candidate)) {
                return strtoupper($candidate);
            }
        }

        return null;
    }

    protected function isIgnoredBlockLine(string $line): bool
    {
        return str_contains($line, 'FACTURA')
            || str_contains($line, 'TAXISTAS NORTE Y SUR DE LANZAROTE')
            || str_contains($line, 'S.COOPERATIVA')
            || str_contains($line, 'FECHA')
            || str_contains($line, 'FECHAS')
            || str_contains($line, 'DESCRIPCIÓN')
            || str_contains($line, 'DESCRIPCION')
            || str_contains($line, 'CIF ')
            || str_contains($line, 'CANTIDAD')
            || str_contains($line, 'PRECIO')
            || str_contains($line, 'TOTALES')
            || str_contains($line, 'IMPORTE')
            || str_contains($line, 'TOTAL')
            || preg_match('/^(ene|feb|mar|abr|may|jun|jul|ago|sep|oct|nov|dic)-\d{2}$/i', $line) === 1
            || preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $line) === 1;
    }

    /**
     * @param  array<int, array{name:string, licencia:string, nif:?string, municipio:string}>  $records
     * @return array<int, array{name:string, licencia:string, nif:?string, municipio:string}>
     */
    protected function deduplicateRecords(array $records): array
    {
        $seen = [];
        $deduplicated = [];

        foreach ($records as $record) {
            $key = implode('|', [
                $record['licencia'],
                $record['nif'] ?? '',
                $record['municipio'],
                $record['name'],
            ]);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $deduplicated[] = $record;
        }

        return $deduplicated;
    }

    /**
     * @param  array{name:string, licencia:string, nif:?string, municipio:string, municipio_id:int|string|null, municipio_db:?string}  $record
     */
    protected function findMatchingUser(array $record): ?User
    {
        if (filled($record['nif'])) {
            $user = User::query()->where('nif', $record['nif'])->first();

            if ($user) {
                return $user;
            }
        }

        $user = User::query()
            ->where('licencia', $record['licencia'])
            ->where('municipio_id', $record['municipio_id'])
            ->first();

        if ($user) {
            return $user;
        }

        $licenseNumber = $this->extractLicenseNumber($record['licencia']);

        if ($licenseNumber !== null) {
            $user = User::query()
                ->where('municipio_id', $record['municipio_id'])
                ->whereNotNull('licencia')
                ->where('licencia', '!=', '')
                ->get()
                ->first(fn (User $candidate): bool => $this->extractLicenseNumber($candidate->licencia) === $licenseNumber);

            if ($user) {
                return $user;
            }
        }

        return User::query()
            ->where('name', $record['name'])
            ->where('municipio_id', $record['municipio_id'])
            ->first();
    }

    /**
     * @param  array{name:string, licencia:string, nif:?string, municipio:string, municipio_id:int|string|null, municipio_db:?string}  $record
     * @return array<string, mixed>
     */
    protected function buildUserPayload(array $record, ?User $user): array
    {
        $email = $user?->email;

        if (! filled($email)) {
            $email = $this->generateImportEmail($record['name'], $record['licencia'], $record['nif']);
        }

        return [
            'status' => true,
            'role' => 'taxista',
            'name' => $record['name'],
            'name_first' => $record['name'],
            'name_last' => null,
            'municipio_id' => $record['municipio_id'],
            'nif' => $record['nif'],
            'licencia' => $record['licencia'],
            'email' => $email,
            'password' => $user?->password ?: Hash::make(Str::random(32)),
        ];
    }

    protected function generateImportEmail(string $name, string $licencia, ?string $nif): string
    {
        $base = Str::slug($name, '.');
        $licensePart = Str::slug($licencia, '');
        $suffix = strtolower($nif ?: Str::random(6));
        $candidate = "{$base}.{$licensePart}.{$suffix}@taxistas.import.local";
        $counter = 1;

        while (User::query()->where('email', $candidate)->exists()) {
            $candidate = "{$base}.{$licensePart}.{$suffix}.{$counter}@taxistas.import.local";
            $counter++;
        }

        return $candidate;
    }

    protected function normalizeMunicipioName(string $municipioName): string
    {
        $normalized = Str::of($municipioName)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z]/', '')
            ->value();

        return $this->municipioAliases[$normalized] ?? $normalized;
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

        return (int) end($matches[0]);
    }

    protected function formatLicense(?int $licenseNumber, string $municipio): ?string
    {
        if ($licenseNumber === null) {
            return null;
        }

        return sprintf('LM %02d %s', $licenseNumber, $this->normalizeMunicipioName($municipio));
    }
}
