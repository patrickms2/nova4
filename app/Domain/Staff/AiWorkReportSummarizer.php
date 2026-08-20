<?php

declare(strict_types=1);

namespace App\Domain\Staff;

use App\Ai\Agents\WorkReportSummaryAgent;
use App\Domain\Staff\Contracts\WorkReportSummarizerContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-backed implementation of the summarizer. Transcribes the voice
 * recording (OpenAI Whisper) and asks an Agent for a short natural-language
 * summary. Never throws: any provider failure is reported through the
 * result array so the domain can preserve the original evidence.
 */
final class AiWorkReportSummarizer implements WorkReportSummarizerContract
{
    public function summarize(string $audioPath): array
    {
        $openAiKey = (string) (config('services.openai.api_key') ?: config('ai.providers.openai.key'));

        if ($openAiKey === '' || ! is_file($audioPath)) {
            return [
                'success' => false,
                'transcription' => null,
                'summary' => null,
                'error' => 'audio_or_provider_unavailable',
            ];
        }

        $transcription = $this->transcribe($audioPath, $openAiKey);

        if ($transcription === null) {
            return [
                'success' => false,
                'transcription' => null,
                'summary' => null,
                'error' => 'transcription_failed',
            ];
        }

        try {
            $response = (new WorkReportSummaryAgent)->prompt($transcription);
            $summary = (string) ($response['summary'] ?? '');

            return [
                'success' => $summary !== '',
                'transcription' => $transcription,
                'summary' => $summary !== '' ? $summary : null,
                'error' => $summary !== '' ? null : 'summary_generation_failed',
            ];
        } catch (\Throwable $exception) {
            Log::warning('WorkReport summary generation failed', [
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'transcription' => $transcription,
                'summary' => null,
                'error' => 'summary_generation_failed',
            ];
        }
    }

    private function transcribe(string $audioPath, string $openAiKey): ?string
    {
        $handle = fopen($audioPath, 'r');

        if ($handle === false) {
            return null;
        }

        try {
            $response = Http::withToken($openAiKey)
                ->timeout(120)
                ->attach('file', $handle, basename($audioPath))
                ->asMultipart()
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => (string) config('services.nova.audio_transcription_model', 'whisper-1'),
                    'language' => (string) config('services.nova.audio_transcription_language', 'es'),
                    'response_format' => 'json',
                ]);
        } catch (\Throwable $exception) {
            Log::warning('WorkReport audio transcription failed', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            fclose($handle);
        }

        if (! $response->successful()) {
            return null;
        }

        $text = trim((string) $response->json('text', ''));

        return $text !== '' ? $text : null;
    }
}
