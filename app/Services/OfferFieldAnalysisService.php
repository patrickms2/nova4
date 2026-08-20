<?php

namespace App\Services;

use App\Models\Offer;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OfferFieldAnalysisService
{
    /**
     * @return array{updates: array<string, mixed>, filled: list<string>}
     */
    public function analyzeAndPrepareUpdates(Offer $offer): array
    {
        $text = $this->buildAnalysisText($offer);

        if (blank(trim($text))) {
            return [
                'updates' => [],
                'filled' => [],
            ];
        }

        $analysis = app(SpeechAnalysisService::class)->analyze($text);

        $updates = [];
        $filled = [];

        $this->fillIfMissing($offer, $updates, $filled, 'title', Arr::get($analysis, 'suggested_title'), 'Título');
        $this->fillIfMissing($offer, $updates, $filled, 'excerpt', Arr::get($analysis, 'suggested_excerpt'), 'Resumen');
        $this->fillIfMissing($offer, $updates, $filled, 'description', Arr::get($analysis, 'normalized_text'), 'Descripción');
        $this->fillIfMissing($offer, $updates, $filled, 'price_from', Arr::get($analysis, 'suggested_price'), 'Precio');
        $this->fillIfMissing($offer, $updates, $filled, 'price_unit', Arr::get($analysis, 'price_unit'), 'Unidad');
        $this->fillIfMissing($offer, $updates, $filled, 'duration_minutes', Arr::get($analysis, 'suggested_duration'), 'Duración');
        $this->fillIfMissing($offer, $updates, $filled, 'image_url', Arr::get($analysis, 'suggested_image_url'), 'Imagen');

        if (blank($offer->slug) && filled($updates['title'] ?? null)) {
            $updates['slug'] = Str::slug((string) $updates['title']);
            $filled[] = 'Slug';
        }

        if (blank($offer->category) && filled(Arr::get($analysis, 'suggested_type'))) {
            $updates['category'] = match (Arr::get($analysis, 'suggested_type')) {
                'restaurant' => 'restaurant',
                'experience' => 'experience',
                'product', 'artisan' => 'product',
                default => 'service',
            };
            $filled[] = 'Categoría';
        }

        if (blank($offer->experience_type)) {
            $suggestedExperienceType = $this->resolveExperienceType($offer, $analysis);

            if (filled($suggestedExperienceType)) {
                $updates['experience_type'] = $suggestedExperienceType;
                $filled[] = 'Tipo de experiencia';
            }
        }

        if (blank($offer->local_tag)) {
            $suggestedLocalTag = $this->resolveLocalTag($offer, $analysis);

            if (filled($suggestedLocalTag)) {
                $updates['local_tag'] = $suggestedLocalTag;
                $filled[] = 'Etiqueta local';
            }
        }

        if (blank($offer->authenticity_score) || (int) $offer->authenticity_score === 0) {
            $updates['authenticity_score'] = $this->resolveAuthenticityScore($offer, $analysis);
            $filled[] = 'Autenticidad';
        }

        if (blank($offer->location_label) && filled(Arr::get($analysis, 'suggested_address'))) {
            $updates['location_label'] = Arr::get($analysis, 'suggested_address');
            $filled[] = 'Ubicación';
        }

        if (blank($offer->lat) && filled(Arr::get($analysis, 'suggested_latitude'))) {
            $updates['lat'] = Arr::get($analysis, 'suggested_latitude');
            $filled[] = 'Latitud';
        }

        if (blank($offer->lng) && filled(Arr::get($analysis, 'suggested_longitude'))) {
            $updates['lng'] = Arr::get($analysis, 'suggested_longitude');
            $filled[] = 'Longitud';
        }

        if (empty($offer->context_tags)) {
            $contextTags = $this->resolveContextTags($offer, $analysis, $updates);

            if ($contextTags !== []) {
                $updates['context_tags'] = $contextTags;
                $filled[] = 'Etiquetas de contexto';
            }
        }

        return [
            'updates' => $updates,
            'filled' => array_values(array_unique($filled)),
        ];
    }

    private function buildAnalysisText(Offer $offer): string
    {
        return implode('. ', array_filter([
            $offer->title,
            $offer->excerpt,
            $offer->description,
            $offer->location_label ? 'Ubicación: '.$offer->location_label : null,
            $offer->category ? 'Categoría: '.$offer->category : null,
            $offer->local_tag ? 'Etiqueta local: '.$offer->local_tag : null,
        ]));
    }

    private function fillIfMissing(
        Offer $offer,
        array &$updates,
        array &$filled,
        string $attribute,
        mixed $value,
        string $label,
    ): void {
        if (! blank($offer->{$attribute}) || blank($value)) {
            return;
        }

        $updates[$attribute] = $value;
        $filled[] = $label;
    }

    private function resolveExperienceType(Offer $offer, array $analysis): ?string
    {
        $type = Arr::get($analysis, 'suggested_type');

        return match ($type) {
            'restaurant' => 'gastronomy',
            'experience' => 'leisure',
            'artisan', 'product' => 'shopping',
            default => $offer->category === 'service' ? 'information' : null,
        };
    }

    private function resolveLocalTag(Offer $offer, array $analysis): ?string
    {
        $text = Str::lower((string) ($offer->description ?: $offer->excerpt ?: $offer->title));

        return match (true) {
            Str::contains($text, ['vino', 'bodega', 'volcan']) => 'vino_volcanico',
            Str::contains($text, ['gofio']) => 'gofio_artesano',
            Str::contains($text, ['mojo', 'papas']) => 'papas_mojo',
            Str::contains($text, ['queso']) => 'queso_canario',
            filled(Arr::get($analysis, 'suggested_address')) => Str::slug((string) Arr::get($analysis, 'suggested_address'), '_'),
            default => null,
        };
    }

    private function resolveAuthenticityScore(Offer $offer, array $analysis): int
    {
        $text = Str::lower((string) ($offer->description ?: $offer->excerpt ?: $offer->title));
        $score = 45;

        if (Arr::get($analysis, 'suggested_address')) {
            $score += 10;
        }

        if (Arr::get($analysis, 'suggested_type') === 'restaurant') {
            $score += 10;
        }

        if (Str::contains($text, ['lanzarote', 'canario', 'local', 'artesano', 'tradicional', 'volcánico'])) {
            $score += 20;
        }

        return min($score, 95);
    }

    /**
     * @param  array<string, mixed>  $pendingUpdates
     * @return list<string>
     */
    private function resolveContextTags(Offer $offer, array $analysis, array $pendingUpdates): array
    {
        $tags = [];
        $type = $pendingUpdates['experience_type'] ?? $offer->experience_type ?? $this->resolveExperienceType($offer, $analysis);
        $location = Str::lower((string) ($pendingUpdates['location_label'] ?? $offer->location_label ?? Arr::get($analysis, 'suggested_address', '')));

        if (filled($type)) {
            $tags[] = match ($type) {
                'gastronomy' => 'food',
                'leisure' => 'experience',
                'shopping' => 'shopping',
                default => 'information',
            };

            $tags[] = $type;
        }

        if (filled($location)) {
            $tags[] = 'near_destination';
            $tags[] = 'lanzarote';

            foreach (['arrecife', 'costa teguise', 'puerto del carmen', 'playa blanca', 'haria', 'yaiza', 'teguise'] as $zone) {
                if (Str::contains($location, $zone)) {
                    $tags[] = Str::slug($zone, '_');
                }
            }
        }

        $text = Str::lower((string) ($offer->description ?: $offer->excerpt ?: $offer->title));

        if (Str::contains($text, ['taxi', 'llegada', 'traslado', 'a la llegada'])) {
            $tags[] = 'after_ride';
        }

        return array_values(array_unique(array_filter($tags)));
    }
}
