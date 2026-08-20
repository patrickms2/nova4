<?php

declare(strict_types=1);

namespace App\Domain\Staff\Contracts;

interface WorkReportSummarizerContract
{
    /**
     * Transcribe the voice recording and produce a short natural-language
     * summary of the work performed. Implementations MUST NOT throw on
     * provider failure; they must return a result flagging failure so the
     * domain can preserve the original evidence and fall back safely.
     *
     * @return array{success: bool, transcription: ?string, summary: ?string, error: ?string}
     */
    public function summarize(string $audioPath): array;
}
