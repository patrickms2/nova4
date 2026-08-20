<?php

namespace App\Console\Commands;

use App\Models\TaxistaDocument;
use App\Services\Taxistas\TaxistaDocumentMultipageImporter;
use Illuminate\Console\Command;

class FixMultipageDocumentReferencesCommand extends Command
{
    protected $signature = 'taxista-documents:fix-multipage-references
        {--dry-run : Show the documents that would be updated without persisting changes}
        {--limit=0 : Maximum number of documents to process}';

    protected $description = 'Fix truncated multipage document references and titles by re-reading stored PDFs';

    public function handle(TaxistaDocumentMultipageImporter $importer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));

        $query = TaxistaDocument::query()
            ->whereIn('document_type', ['agencias', 'cuotas', 'repuestos', 'seguro'])
            ->whereNotNull('meta->reference')
            ->whereRaw('CHAR_LENGTH(JSON_UNQUOTE(JSON_EXTRACT(meta, "$.reference"))) <= 2')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $documents = $query->get();

        if ($documents->isEmpty()) {
            $this->info('No se encontraron documentos con referencia corta.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($documents as $document) {
            $absolutePath = storage_path('app/public/' . ltrim($document->file_path, '/'));

            if (! is_file($absolutePath)) {
                $errors++;
                $this->warn("ID {$document->id}: no existe el fichero {$absolutePath}");

                continue;
            }

            try {
                $metadata = $importer->extractMetadataFromPdfPath($absolutePath);
            } catch (\Throwable $e) {
                $errors++;
                $this->warn("ID {$document->id}: error leyendo PDF ({$e->getMessage()})");

                continue;
            }

            if (! $metadata['has_reliable_reference']) {
                $skipped++;
                $this->line("ID {$document->id}: sin referencia fiable");

                continue;
            }

            $year = (int) ($metadata['year'] ?? optional($document->uploaded_at)->format('Y') ?? now()->format('Y'));
            $month = (int) ($metadata['month'] ?? optional($document->uploaded_at)->format('m') ?? now()->format('m'));
            $newReference = (string) $metadata['reference'];
            $currentReference = (string) data_get($document->meta, 'reference', '');
            $newTitle = $importer->buildDocumentTitle($document->document_type, $newReference, $year, $month);

            if ($currentReference === $newReference && $document->title === $newTitle) {
                $skipped++;
                continue;
            }

            $this->line("ID {$document->id}: {$currentReference} -> {$newReference}");

            if (! $dryRun) {
                $meta = $document->meta ?? [];
                $meta['reference'] = $newReference;
                $meta['year'] = $year;
                $meta['month'] = $month;

                $document->forceFill([
                    'title' => $newTitle,
                    'meta' => $meta,
                ])->save();
            }

            $updated++;
        }

        $summary = sprintf(
            'Procesados: %d | Actualizados: %d | Omitidos: %d | Errores: %d%s',
            $documents->count(),
            $updated,
            $skipped,
            $errors,
            $dryRun ? ' | Modo: dry-run' : '',
        );

        $this->newLine();
        $this->info($summary);

        return self::SUCCESS;
    }
}
