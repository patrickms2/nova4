<?php

declare(strict_types=1);

namespace App\Domain\Staff;

use App\Domain\Staff\Contracts\WorkReportSummarizerContract;

/**
 * Safe fallback implementation. Used when no AI provider is configured, or
 * as the last resort when the real summarizer fails. Never blocks the
 * Staff Access workflow: the original audio evidence remains available even
 * without a generated summary.
 */
final class NullWorkReportSummarizer implements WorkReportSummarizerContract
{
    public function summarize(string $audioPath): array
    {
        return [
            'success' => false,
            'transcription' => null,
            'summary' => null,
            'error' => 'summarizer_unavailable',
        ];
    }
}
