<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaAiKnowledge;
use App\Models\NovaBusiness;
use App\Models\NovaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class NovaWebsiteKnowledgeImporter
{
    public function import(NovaBusiness $business): NovaAiKnowledge
    {
        $url = $this->normalizeUrl((string) $business->website_url);

        if ($url === '') {
            throw new RuntimeException('El cliente no tiene URL web configurada.');
        }

        $response = Http::timeout(15)
            ->accept('text/html,application/xhtml+xml')
            ->withUserAgent('NovaKnowledgeImporter/1.0')
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('No se pudo leer la web. Código HTTP: '.$response->status());
        }

        $content = $this->extractText((string) $response->body());

        if (mb_strlen($content) < 200) {
            throw new RuntimeException('La web no contiene suficiente texto útil para importar.');
        }

        $service = $this->aiWhatsappService($business);
        $aiProfile = $service?->aiProfiles()->where('status', 'active')->first()
            ?? $business->aiProfiles()->where('status', 'active')->first()
            ?? $business->aiProfiles()->first();

        return NovaAiKnowledge::query()->updateOrCreate(
            [
                'nova_business_id' => $business->id,
                'title' => 'Web: '.parse_url($url, PHP_URL_HOST),
            ],
            [
                'nova_service_id' => $service?->id,
                'nova_ai_profile_id' => $aiProfile?->id,
                'content' => Str::limit($content, 12000, ''),
                'status' => 'active',
                'metadata' => [
                    'source' => 'website_import',
                    'source_url' => $url,
                    'imported_at' => now()->toIso8601String(),
                ],
            ],
        );
    }

    private function aiWhatsappService(NovaBusiness $business): ?NovaService
    {
        return $business->services()
            ->where('has_whatsapp', true)
            ->whereHas('aiProfiles')
            ->first();
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return 'https://'.$url;
        }

        return $url;
    }

    private function extractText(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<noscript\b[^>]*>.*?<\/noscript>/is', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
