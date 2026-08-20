<?php

namespace App\Filament\App\Resources\TaxistaDocuments\Pages;

use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use App\Support\TaxistaDocumentTypes;
use App\Models\TaxistaDocument;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ViewTaxistaDocument extends ViewRecord
{
    protected static string $resource = TaxistaDocumentResource::class;

    protected string $view = 'filament.app.resources.taxista-documents.pages.view-taxista-document';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function backUrl(): string
    {
        $folder = $this->record->document_type ?: 'OTROS';

        return route('filament.portal.pages.taxista-portal', [
            'tab' => 'documentos',
            'folder' => $folder,
        ]);
    }

    public function openUrl(): ?string
    {
        return $this->resolveDocumentPublicUrl($this->record);
    }

    public function canPreviewPdf(): bool
    {
        $url = $this->openUrl();

        return filled($url) && strtolower((string) pathinfo((string) $this->record->file_path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function fileName(): string
    {
        $fileName = basename((string) $this->record->file_path);

        return $fileName !== '' && $fileName !== '.' ? $fileName : 'Sin archivo';
    }

    public function uploadedAtLabel(): string
    {
        return ($this->record->uploaded_at ?? $this->record->created_at)?->format('d/m/Y H:i') ?? '-';
    }

    public function documentTypeLabel(): string
    {
        return TaxistaDocumentTypes::label($this->record->document_type);
    }

    public function referenceLabel(): string
    {
        $reference = data_get($this->record->meta, 'reference');

        return filled($reference) ? (string) $reference : 'Sin referencia';
    }

    public function notesLabel(): string
    {
        $notes = data_get($this->record->meta, 'notes');

        if (is_array($notes)) {
            return json_encode($notes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '-';
        }

        if (filled($notes)) {
            return (string) $notes;
        }

        if (filled($this->record->notas)) {
            return (string) $this->record->notas;
        }

        if (filled($this->record->meta)) {
            return json_encode($this->record->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '-';
        }

        return '-';
    }

    public function favoriteLabel(): string
    {
        return $this->record->is_favorite ? 'Favorito' : 'No favorito';
    }

    public function toggleFavorite(): void
    {
        $this->record->is_favorite = ! (bool) $this->record->is_favorite;
        $this->record->save();

        Notification::make()
            ->title($this->record->is_favorite ? 'Marcado como favorito' : 'Favorito eliminado')
            ->success()
            ->send();
    }

    private function resolveDocumentPublicUrl(TaxistaDocument $record): ?string
    {
        $path = trim((string) $record->file_path);

        if ($path === '') {
            return null;
        }

        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        if (Str::startsWith($path, 'documentos/')) {
            $path = Str::after($path, 'documentos/');
        }

        $baseUrl = (string) config('filesystems.disks.documentos.url');

        if ($baseUrl !== '' && Storage::disk('documentos')->exists($path)) {
            return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
        }

        return null;
    }
}
