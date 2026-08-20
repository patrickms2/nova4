<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class VoiceController extends Controller
{
    public function transcribe(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => ['required', 'file', 'max:25600'],
        ]);

        $file = $request->file('audio');

        if (! config('services.openai.api_key')) {
            return response()->json([
                'ok' => false,
                'message' => 'Configuración de IA no disponible (API Key faltante)',
            ], 422);
        }

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(120)
            ->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName() ?: 'voice.webm'
            )
            ->asMultipart()
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
            ]);

        if (! $response->successful()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error transcribiendo audio',
                'error' => $response->json(),
            ], 422);
        }

        $rawText = trim((string) data_get($response->json(), 'text', ''));

        // Análisis contextual para TAXILANZ
        $analysis = $this->analyzeServiceSpeech($rawText);

        return response()->json([
            'ok' => true,
            'text' => $rawText,
            'analysis' => $analysis,
        ]);
    }

    private function analyzeServiceSpeech(string $text): array
    {
        // Normalización previa del texto para limpiar dictados tipo "coma", espacios y puntuación repetida
        $normalized = $this->normalizeServiceText($text);
        $lower = Str::lower($normalized);
        $plainLower = Str::lower(Str::ascii($normalized));

        $type = 'service';
        if (Str::contains($lower, ['comer', 'restaurante', 'comida', 'plato', 'cena', 'almuerzo'])) {
            $type = 'restaurant';
        } elseif (Str::contains($lower, ['experiencia', 'tour', 'visita', 'guia', 'guía', 'excursion', 'excursión', 'excursiones', 'clase', 'clases', 'curso', 'cata'])) {
            // "cata" se considera experiencia
            $type = 'experience';
        } elseif (Str::contains($lower, ['taller', 'artesania', 'artesanía', 'hecho a mano', 'barro', 'tejido'])) {
            $type = 'artisan';
        }

        // Extracción de entidades (precios, duraciones)
        $price = null;
        if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(?:euros?|€)/', $lower, $matches)) {
            $price = (float) str_replace(',', '.', $matches[1]);
        } elseif (preg_match('/(?:vale|cuesta|por|precio)\s*(\d+(?:[\.,]\d+)?)/', $lower, $matches)) {
            $price = (float) str_replace(',', '.', $matches[1]);
        } elseif (preg_match('/(\d+(?:[\.,]\d+)?)\s*(?:por\s*persona|pax)/', $lower, $matches)) {
            $price = (float) str_replace(',', '.', $matches[1]);
        }

        $duration = null;
        if (Str::contains($lower, ['día', 'jornada', 'completo'])) {
            $duration = 480; // 8 horas en minutos
        } elseif (preg_match('/(\d+)\s*(min|minutos)/', $lower, $matches)) {
            $duration = (int) $matches[1];
        } elseif (preg_match('/(\d+)\s*(h|hr|hora|horas)/', $lower, $matches)) {
            $duration = (int) $matches[1] * 60;
        } elseif (preg_match('/una\s*hora/', $lower)) {
            $duration = 60;
        }

        // Redondear duración si existe (a múltiplos de 15 min para premium feel, mínimo 15)
        if ($duration !== null) {
            $duration = max(15, (int) (round($duration / 15) * 15));
        }

        $perPerson = Str::contains($lower, ['persona', 'personas', 'pax', 'por cabeza']);

        // Detección de población en Lanzarote → dirección y coordenadas
        $towns = [
            'arrecife' => ['Arrecife, Lanzarote', 28.963021, -13.547693],
            'puerto del carmen' => ['Puerto del Carmen, Tías', 28.924355, -13.661337],
            'playa blanca' => ['Playa Blanca, Yaiza', 28.867163, -13.828278],
            'costa teguise' => ['Costa Teguise, Teguise', 28.998567, -13.487019],
            'teguise' => ['Teguise, Lanzarote', 29.060236, -13.560047],
            'haria' => ['Haría, Lanzarote', 29.145244, -13.495236],
            'haría' => ['Haría, Lanzarote', 29.145244, -13.495236],
            'tinajo' => ['Tinajo, Lanzarote', 29.060552, -13.672301],
            'san bartolome' => ['San Bartolomé, Lanzarote', 28.995360, -13.613520],
            'san bartolomé' => ['San Bartolomé, Lanzarote', 28.995360, -13.613520],
            'yaiza' => ['Yaiza, Lanzarote', 28.956271, -13.765835],
            'orzola' => ['Órzola, Lanzarote', 29.214592, -13.454385],
            'órzola' => ['Órzola, Lanzarote', 29.214592, -13.454385],
            'famara' => ['Caleta de Famara, Lanzarote', 29.120411, -13.557946],
        ];

        $detectedAddress = null;
        $detectedLat = null;
        $detectedLng = null;
        foreach ($towns as $needle => $meta) {
            $needlePlain = Str::lower(Str::ascii($needle));
            if (Str::contains($plainLower, $needlePlain)) {
                [$detectedAddress, $detectedLat, $detectedLng] = $meta;
                break;
            }
        }

        // Generación de título sugerido más inteligente
        $suggestedTitle = null;
        if (Str::length($normalized) > 0) {
            $words = preg_split('/\s+/', trim($normalized));
            $titleWords = array_slice($words, 0, 5); // primeras palabras significativas
            $suggestedTitle = $this->titleCasePreservingPrepositions(implode(' ', $titleWords));
            if (Str::length($suggestedTitle) < 5 && isset($words[5])) {
                $suggestedTitle .= ' '.Str::title($words[5]);
            }
        }

        // Resumen limpio (extracto) con comas y punto final + duración si procede
        $excerpt = $normalized;
        if ($perPerson) {
            $excerpt = preg_replace('/euros?\s*(?:por\s*)?personas?/', 'euros por persona', $excerpt);
        }
        $excerpt = trim(preg_replace('/\s+/', ' ', $excerpt));
        if ($duration !== null) {
            // Añadimos una frase final estandarizada con duración si no existe ya explícita
            if (! preg_match('/\b(min|minutos|hora|horas|h|hr)\b/', Str::lower($excerpt))) {
                $excerpt .= '. '.$duration.' min';
            }
        }

        return [
            'suggested_type' => $type,
            'suggested_price' => $price,
            'suggested_duration' => $duration,
            'suggested_title' => $suggestedTitle,
            'is_per_person' => $perPerson,
            'is_relevant' => Str::length($normalized) > 10,
            'suggested_address' => $detectedAddress,
            'suggested_latitude' => $detectedLat,
            'suggested_longitude' => $detectedLng,
            'normalized_text' => $normalized,
            'suggested_excerpt' => $excerpt,
        ];
    }

    private function normalizeServiceText(string $text): string
    {
        $t = ' '.trim($text).' ';
        // "coma" dictado → ","
        $t = preg_replace('/\s+coma\s+/iu', ', ', $t);
        // Doble puntuación o espacios antes de puntuación
        $t = preg_replace('/\s*([,.;:])\s*/u', '$1 ', $t);
        // Múltiples puntos → uno solo
        $t = preg_replace('/\.{2,}/', '.', $t);
        // euros personas → euros por persona
        $t = preg_replace('/euros?\s*personas?/iu', 'euros por persona', $t);
        $t = preg_replace('/euros?\s*persona/iu', 'euros por persona', $t);
        // 1 h / 1hr → 1h
        $t = preg_replace('/\b(\d+)\s*h(r)?\b/iu', '$1h', $t);
        // Colapsar espacios
        $t = preg_replace('/\s{2,}/', ' ', $t);
        $t = trim($t);
        // Poner mayúscula inicial y asegurar punto final si no lo hay
        $t = ucfirst($t);
        if (! preg_match('/[\.!?]$/', $t)) {
            $t .= '.';
        }

        return $t;
    }

    private function titleCasePreservingPrepositions(string $phrase): string
    {
        $small = ['de', 'la', 'las', 'los', 'del', 'al', 'por', 'para', 'en', 'y', 'o', 'a'];
        $words = preg_split('/\s+/', trim($phrase));
        $out = [];
        foreach ($words as $i => $w) {
            $lw = Str::lower($w);
            if ($i > 0 && in_array($lw, $small, true)) {
                $out[] = $lw;
            } else {
                $out[] = Str::title($lw);
            }
        }

        return implode(' ', $out);
    }
}
