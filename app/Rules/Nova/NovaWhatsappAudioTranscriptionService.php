<?php

declare(strict_types=1);

namespace App\Services\Nova;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class NovaWhatsappAudioTranscriptionService
{
    /**
     * @return array{success:bool,text?:string,error?:string,media_id?:string,path?:string}
     */
    public function transcribe(string $mediaId): array
    {
        $accessToken = (string) config('services.nova.whatsapp_access_token');
        $openAiKey = (string) (config('services.openai.api_key') ?: config('ai.providers.openai.key'));

        if ($accessToken === '') {
            return [
                'success' => false,
                'error' => 'WhatsApp access token is not configured',
                'media_id' => $mediaId,
            ];
        }

        if ($openAiKey === '') {
            return [
                'success' => false,
                'error' => 'OpenAI API key is not configured',
                'media_id' => $mediaId,
            ];
        }

        $media = $this->fetchMediaMetadata($mediaId, $accessToken);

        if (! $media['success']) {
            return $media;
        }

        $audio = $this->downloadAudio((string) $media['url'], $accessToken, (string) ($media['mime_type'] ?? 'audio/ogg'));

        if (! $audio['success']) {
            return $audio + ['media_id' => $mediaId];
        }

        $transcription = $this->transcribeAudio((string) $audio['path'], $openAiKey);

        return $transcription + [
            'media_id' => $mediaId,
            'path' => $audio['path'],
        ];
    }

    /**
     * @return array{success:bool,url?:string,mime_type?:string,error?:string,media_id:string}
     */
    private function fetchMediaMetadata(string $mediaId, string $accessToken): array
    {
        $version = (string) config('services.nova.meta_graph_version', 'v22.0');
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get("https://graph.facebook.com/{$version}/{$mediaId}");

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'Unable to fetch WhatsApp media metadata: '.$response->body(),
                'media_id' => $mediaId,
            ];
        }

        $url = $response->json('url');

        if (! is_string($url) || $url === '') {
            return [
                'success' => false,
                'error' => 'WhatsApp media URL was not returned',
                'media_id' => $mediaId,
            ];
        }

        return [
            'success' => true,
            'url' => $url,
            'mime_type' => (string) $response->json('mime_type', 'audio/ogg'),
            'media_id' => $mediaId,
        ];
    }

    /**
     * @return array{success:bool,path?:string,error?:string}
     */
    private function downloadAudio(string $url, string $accessToken, string $mimeType): array
    {
        $response = Http::withToken($accessToken)->get($url);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'Unable to download WhatsApp audio: '.$response->body(),
            ];
        }

        $extension = str_contains($mimeType, 'mpeg') ? 'mp3' : 'ogg';
        $path = 'nova/whatsapp/audio/'.Str::uuid().'.'.$extension;

        Storage::disk('local')->put($path, $response->body());

        return [
            'success' => true,
            'path' => Storage::disk('local')->path($path),
        ];
    }

    /**
     * @return array{success:bool,text?:string,error?:string}
     */
    private function transcribeAudio(string $path, string $openAiKey): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [
                'success' => false,
                'error' => 'Unable to open downloaded audio file',
            ];
        }

        try {
            $response = Http::withToken($openAiKey)
                ->timeout(120)
                ->attach('file', $handle, basename($path))
                ->asMultipart()
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => (string) config('services.nova.audio_transcription_model', 'whisper-1'),
                    'language' => (string) config('services.nova.audio_transcription_language', 'es'),
                    'response_format' => 'json',
                ]);
        } finally {
            fclose($handle);
        }

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'Unable to transcribe audio: '.$response->body(),
            ];
        }

        $text = trim((string) $response->json('text', ''));

        if ($text === '') {
            return [
                'success' => false,
                'error' => 'Audio transcription returned empty text',
            ];
        }

        return [
            'success' => true,
            'text' => $text,
        ];
    }
}
