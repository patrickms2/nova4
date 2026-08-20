<?php

namespace App\Actions\Community;

use App\Models\CommunityDocumentImport;
use App\Models\CommunityOwnerDocument;
use App\Models\Person;
use App\Models\Property;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class ImportOwnerDocuments
{
    public function handle(CommunityDocumentImport $import): CommunityDocumentImport
    {
        $import->update(['status' => 'processing', 'issues' => []]);
        $temporaryDirectory = storage_path('app/community-imports/'.Str::uuid());

        try {
            if (! is_dir($temporaryDirectory) && ! mkdir($temporaryDirectory, 0700, true) && ! is_dir($temporaryDirectory)) {
                throw new RuntimeException('No se pudo crear el directorio temporal.');
            }

            $source = Storage::disk('local')->path($import->source_path);
            $files = $this->extractFiles($source, $temporaryDirectory);
            $issues = [];
            $created = 0;

            foreach ($files as $file) {
                $match = $this->resolveOwner($import, $file);

                if ($match === null) {
                    $issues[] = ['file' => basename($file), 'reason' => 'No se encontró un propietario o propiedad inequívocos.'];

                    continue;
                }

                DB::transaction(function () use ($import, $file, $match): void {
                    $destination = 'community/owners/'.Str::uuid().'.pdf';
                    Storage::disk('local')->put($destination, file_get_contents($file));

                    CommunityOwnerDocument::create([
                        'community_id' => $import->community_id,
                        'person_id' => $match['person']->id,
                        'property_id' => $match['property']?->id,
                        'community_document_type_id' => $import->community_document_type_id,
                        'type' => $import->documentType?->code ?? 'other',
                        'title' => pathinfo(basename($file), PATHINFO_FILENAME),
                        'path' => $destination,
                        'status' => 'active',
                        'metadata' => ['import_id' => $import->id, 'original_name' => basename($file)],
                        'uploaded_by' => $import->created_by,
                    ]);
                });

                $created++;
            }

            $import->update([
                'status' => $issues === [] ? 'completed' : 'completed_with_issues',
                'files_found' => count($files),
                'documents_created' => $created,
                'unmatched_files' => count($issues),
                'issues' => $issues,
                'processed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $import->update(['status' => 'failed', 'issues' => [['reason' => $exception->getMessage()]], 'processed_at' => now()]);
        } finally {
            if (is_dir($temporaryDirectory)) {
                collect(glob($temporaryDirectory.'/*') ?: [])->each(fn (string $path) => is_file($path) ? unlink($path) : null);
                rmdir($temporaryDirectory);
            }
        }

        return $import->refresh();
    }

    /** @return list<string> */
    private function extractFiles(string $source, string $directory): array
    {
        if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'zip') {
            $archive = new ZipArchive;

            if ($archive->open($source) !== true) {
                throw new RuntimeException('El ZIP no se puede abrir.');
            }

            $files = [];
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $name = $archive->getNameIndex($index);
                if ($name === false || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') {
                    continue;
                }

                $target = $directory.'/'.Str::uuid().'-'.basename($name);
                file_put_contents($target, $archive->getFromIndex($index));
                $files[] = $target;
            }
            $archive->close();

            return $files;
        }

        $pattern = $directory.'/page-%04d.pdf';
        $process = new Process([config('community.pdfseparate_path', '/opt/homebrew/bin/pdfseparate'), $source, $pattern]);
        $process->run();

        if (! $process->isSuccessful()) {
            $target = $directory.'/'.basename($source);
            copy($source, $target);

            return [$target];
        }

        return array_values(glob($directory.'/page-*.pdf') ?: []);
    }

    /** @return array{person: Person, property: ?Property}|null */
    private function resolveOwner(CommunityDocumentImport $import, string $file): ?array
    {
        $textProcess = new Process([config('community.pdftotext_path', '/opt/homebrew/bin/pdftotext'), '-layout', $file, '-']);
        $textProcess->run();
        $haystack = Str::lower(basename($file).' '.$textProcess->getOutput());
        $normalize = static fn (?string $value): string => preg_replace('/[^a-z0-9]/i', '', Str::lower($value ?? '')) ?? '';
        $normalizedHaystack = $normalize($haystack);

        $people = $import->community->people()->wherePivot('role', 'owner')->get();
        $personMatches = $people->filter(fn (Person $person): bool => ($identifier = $normalize($person->document_number)) !== '' && str_contains($normalizedHaystack, $identifier));
        $properties = $import->community->properties()->with('people')->get();
        $propertyMatches = $properties->filter(fn (Property $property): bool => ($reference = $normalize($property->unit_reference)) !== '' && str_contains($normalizedHaystack, $reference));

        if ($propertyMatches->count() === 1) {
            $property = $propertyMatches->first();
            $person = $personMatches->count() === 1 ? $personMatches->first() : $property?->people->first();

            return $person ? ['person' => $person, 'property' => $property] : null;
        }

        return $personMatches->count() === 1 ? ['person' => $personMatches->first(), 'property' => null] : null;
    }
}
