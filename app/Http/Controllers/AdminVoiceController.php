<?php

namespace App\Http\Controllers;

use App\Models\CollectionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdminVoiceController extends Controller
{
    public function transcribe(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => ['required', 'file', 'max:25600'],
        ]);

        $file = $request->file('audio');

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(120)
            ->attach(
                'file',
                fopen($file->getRealPath(), 'r'),
                $file->getClientOriginalName() ?: 'voice.webm'
            )
            ->asMultipart()
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => config('services.openai.transcribe_model', 'gpt-4o-transcribe'),
            ]);

        if (! $response->successful()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error transcribiendo audio',
                'error' => $response->json(),
            ], 422);
        }

        $rawText = trim((string) data_get($response->json(), 'text', ''));

        $parsed = $this->parseDiscSpeech($rawText);

        return response()->json([
            'ok' => true,
            'raw_text' => $rawText,
            ...$parsed,
        ]);
    }

    public function transcribeMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => ['required', 'file', 'max:25600'],
            'collection_id' => ['required', 'integer'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ]);

        $file = $request->file('audio');
        $photoFile = $request->file('photo');

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(120)
            ->attach(
                'file',
                fopen($file->getRealPath(), 'r'),
                $file->getClientOriginalName() ?: 'voice.webm'
            )
            ->asMultipart()
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => config('services.openai.transcribe_model', 'gpt-4o-transcribe'),
            ]);

        if (! $response->successful()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error transcribiendo audio',
                'error' => $response->json(),
            ], 422);
        }

        $rawText = trim((string) data_get($response->json(), 'text', ''));
        $discs = $this->parseMultipleDiscs($rawText);

        // If we have a photo, try to use it for complementary recognition
        $photoResults = [];
        if ($photoFile) {
            $photoResults = $this->recognizeFromPhoto($photoFile);
        }

        $collectionId = (int) $request->input('collection_id');
        $created = 0;

        // Combine or augment results
        if (empty($discs) && ! empty($photoResults)) {
            $discs = $photoResults;
        } elseif (! empty($photoResults)) {
            // For now, we'll just add the photo results as extra discs if they don't seem to overlap
            // or we could try to merge. Simpler: just add them.
            foreach ($photoResults as $pDisc) {
                $discs[] = $pDisc;
            }
        }

        $savedPhotoPath = null;
        if ($photoFile) {
            $savedPhotoPath = $photoFile->store('collection-items', 'public');
        }

        foreach ($discs as $index => $disc) {
            $artist = $disc['artist'] ?? null;
            $title = $disc['title'] ?? null;

            if (! $artist && ! $title) {
                continue;
            }

            $item = CollectionItem::create([
                'collection_id' => $collectionId,
                'artist' => $artist,
                'title' => $title,
                'country' => $disc['country'] ?? null,
                'year' => $disc['year'] ?? null,
                'voice_input' => $disc['raw_segment'] ?? ($photoFile ? 'Reconocimiento por foto' : ''),
                'classification' => 'lot',
                'priority_score' => 0,
                'photos' => $savedPhotoPath ? [$savedPhotoPath] : null,
            ]);

            $disc['id'] = $item->id;
            $discs[$index] = $disc;
            $created++;
        }

        return response()->json([
            'ok' => true,
            'raw_text' => $rawText,
            'created' => $created,
            'discs' => $discs,
        ]);
    }

    private function recognizeFromPhoto($file): array
    {
        try {
            $base64Image = base64_encode(file_get_contents($file->getRealPath()));
            $mimeType = $file->getMimeType();

            $response = Http::withToken(config('services.openai.api_key'))
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Realiza un OCR inteligente para identificar discos de vinilo o CDs en esta imagen. Extrae el nombre del artista, el título del álbum, el país y el año si son visibles en las etiquetas o portadas. Devuelve una lista en formato JSON con los campos "artist", "title", "country" y "year" para cada disco detectado. Si no estás seguro de algún campo, déjalo nulo. Devuelve SOLO el JSON, sin bloques de código ni texto adicional.',
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:{$mimeType};base64,{$base64Image}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = data_get($data, 'choices.0.message.content');
                $decoded = json_decode($content, true);

                // Assume it returns { "discs": [...] } or similar
                $results = $decoded['discs'] ?? $decoded['items'] ?? (is_array($decoded) && isset($decoded[0]) ? $decoded : []);

                return is_array($results) ? $results : [];
            }
        } catch (\Exception $e) {
            logger()->error('Photo recognition failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    private function parseMultipleDiscs(string $text): array
    {
        $separators = '/\b(?:siguiente|next|nuevo disco|new disc)\b|[.;]/i';
        $segments = preg_split($separators, $text);
        $discs = [];

        foreach ($segments as $segment) {
            $segment = trim($segment, " \t\n\r\0\x0B,");

            if ($segment === '' || Str::wordCount($segment) < 2) {
                continue;
            }

            $parsed = $this->parseDiscSpeech($segment);

            if (! empty($parsed['artist']) || ! empty($parsed['title'])) {
                $parsed['raw_segment'] = $segment;
                $discs[] = $parsed;
            }
        }

        return $discs;
    }

    private function parseDiscSpeech(string $text): array
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', $text));

        $countryMap = [
            'uk' => 'UK',
            'u k' => 'UK',
            'united kingdom' => 'UK',
            'reino unido' => 'UK',
            'germany' => 'Germany',
            'alemania' => 'Germany',
            'japan' => 'Japan',
            'japón' => 'Japan',
            'spain' => 'Spain',
            'españa' => 'Spain',
            'usa' => 'USA',
            'u s a' => 'USA',
            'united states' => 'USA',
            'estados unidos' => 'USA',
            'holland' => 'Holland',
            'holanda' => 'Holland',
            'netherlands' => 'Holland',
        ];

        $country = null;
        $countryMatchedText = null;
        $year = null;

        $lower = Str::lower($normalized);

        foreach ($countryMap as $needle => $mapped) {
            if (str_contains($lower, $needle)) {
                $country = $mapped;
                $countryMatchedText = $needle;
                break;
            }
        }

        if (preg_match('/\b(19[5-9]\d|20[0-2]\d)\b/', $normalized, $m)) {
            $year = $m[1];
        }

        $working = $normalized;

        if ($countryMatchedText) {
            $working = preg_replace('/\b'.preg_quote($countryMatchedText, '/').'\b/i', '', $working);
        }

        if ($year) {
            $working = preg_replace('/\b'.preg_quote($year, '/').'\b/', '', $working);
        }

        $working = trim((string) preg_replace('/\s+/', ' ', $working));

        $artist = null;
        $title = null;

        if (str_contains($working, ' - ')) {
            [$artist, $title] = array_map('trim', explode(' - ', $working, 2));
        } elseif (str_contains($working, ' – ')) { // En-dash
            [$artist, $title] = array_map('trim', explode(' – ', $working, 2));
        } elseif (str_contains($working, ' — ')) { // Em-dash
            [$artist, $title] = array_map('trim', explode(' — ', $working, 2));
        } elseif (str_contains($working, ',')) {
            $parts = array_map('trim', explode(',', $working, 2));
            $artist = $parts[0] ?? null;
            $title = $parts[1] ?? null;
        } else {
            $title = $working ?: null;
        }

        return [
            'artist' => $artist ?: null,
            'title' => $title ?: null,
            'country' => $country,
            'year' => $year,
        ];
    }

    public function deleteDisc(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $item = CollectionItem::find($request->input('id'));

        if ($item) {
            $item->delete();

            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => false, 'message' => 'Disco no encontrado'], 404);
    }
}
