<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot;

use App\Domain\Nova\Copilot\Enums\InputType;
use App\Domain\Nova\Copilot\ValueObjects\Input;
use App\Services\Nova\NovaWhatsappAudioTranscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final readonly class MediaProcessor
{
    public function __construct(
        private NovaWhatsappAudioTranscriptionService $audioTranscription,
    ) {}

    public function process(Request $request, Input $input): Input
    {
        if ($input->type === InputType::AUDIO) {
            return $this->processAudio($input);
        }

        if ($input->type === InputType::IMAGE) {
            return $this->processImage($input);
        }

        if ($input->type === InputType::DOCUMENT) {
            return $this->processDocument($input);
        }

        return $input;
    }

    private function processAudio(Input $input): Input
    {
        $mediaId = $input->mediaId;

        if ($mediaId === null || $mediaId === '') {
            return new Input(InputType::TEXT, '[audio sin contenido]', $input->channel);
        }

        $result = $this->audioTranscription->transcribe($mediaId);

        if (! ($result['success'] ?? false)) {
            Log::warning('Copilot v2: audio transcription failed', [
                'error' => $result['error'] ?? 'unknown',
                'media_id' => $mediaId,
            ]);

            return new Input(InputType::TEXT, '[no se pudo transcribir el audio]', $input->channel);
        }

        $text = trim((string) ($result['text'] ?? ''));

        return new Input(InputType::TEXT, $text === '' ? '[audio vacío]' : $text, $input->channel);
    }

    private function processImage(Input $input): Input
    {
        if ($input->mediaId === null || $input->mediaId === '') {
            return new Input(InputType::TEXT, '[imagen]', $input->channel);
        }

        // TODO: implement vision analysis for receipts, documents, damages, products, etc.
        return new Input(InputType::TEXT, '[imagen recibida, análisis pendiente]', $input->channel);
    }

    private function processDocument(Input $input): Input
    {
        if ($input->mediaId === null || $input->mediaId === '') {
            return new Input(InputType::TEXT, '[documento]', $input->channel);
        }

        // TODO: implement OCR / document analysis
        return new Input(InputType::TEXT, '[documento recibido, análisis pendiente]', $input->channel);
    }
}
