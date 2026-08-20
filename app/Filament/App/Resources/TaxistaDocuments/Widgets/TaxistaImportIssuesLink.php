<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaxistaDocuments\Widgets;

use App\Filament\App\Pages\TaxistaImportIssues;
use App\Services\Taxistas\TaxistaImportIssueBoard;
use Filament\Widgets\Widget;

class TaxistaImportIssuesLink extends Widget
{
    protected string $view = 'filament.app.resources.taxista-documents.widgets.taxista-import-issues-link';

    protected int|string|array $columnSpan = 'full';

    public int $openIssuesCount = 0;

    /** @var array<string, int> */
    public array $byDocumentType = [];

    public string $issuesUrl = '';

    public function mount(): void
    {
        $board = app(TaxistaImportIssueBoard::class)->build();

        $this->openIssuesCount = (int) ($board['total'] ?? 0);
        $this->byDocumentType = $board['by_document_type'] ?? [];
        $this->issuesUrl = TaxistaImportIssues::getUrl(panel: 'app');
    }
}
