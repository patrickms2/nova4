<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions\Executors;

use App\Domain\Nova\Missions\MissionArtifact;
use App\Domain\Nova\Missions\MissionEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final readonly class WebsiteDiscoveryExecutor implements MissionStepExecutor
{
    public function supports(array $step, array $mission): bool
    {
        return ($step['capability_id'] ?? null) === 'knowledge'
            && ! empty($mission['context']['website']);
    }

    /** @return array<string, mixed> */
    public function execute(array $step, array $mission): array
    {
        $url = (string) $mission['context']['website'];
        $mission['events'][] = MissionEvent::make(
            'connector.request',
            'Solicitud del conector',
            $url,
            $step['connector'] ?: 'Conector de conocimiento',
        )->toArray();

        try {
            $response = Http::timeout(10)->get($url);
            $body = $response->body();
            $facts = $this->extractFacts($body, $url);

            $mission['events'][] = MissionEvent::make(
                'connector.response',
                'Respuesta del conector',
                '200 OK · '.$facts['title'],
                $step['connector'] ?: 'Conector de conocimiento',
            )->toArray();

            $mission['context']['discovered_facts'] = $facts;
        } catch (\Throwable $exception) {
            $mission['events'][] = MissionEvent::make(
                'connector.response',
                'Error del conector',
                'No se pudo acceder a la web: '.$exception->getMessage(),
                $step['connector'] ?: 'Conector de conocimiento',
            )->toArray();

            $mission['context']['discovered_facts'] = [
                'url' => $url,
                'title' => 'No disponible',
                'description' => 'No se pudo analizar la web.',
                'language' => null,
            ];
        }

        $mission['context']['discovered_facts']['analyzed_at'] = now()->toIso8601String();

        $artifact = MissionArtifact::make('website-analysis.json', $mission['goal'])->toArray();
        $artifact['source'] = $url;
        $artifact['facts'] = $mission['context']['discovered_facts'];
        $mission['artifacts'][] = $artifact;

        $mission['events'][] = MissionEvent::make(
            'artifact.generated',
            'Artefacto generado',
            $artifact['name'],
            $step['agent'],
        )->toArray();

        $index = $this->runningStepIndex($mission['steps']);
        if ($index !== null) {
            $mission['steps'][$index]['status'] = 'completed';
            $mission['steps'][$index]['progress'] = 100;
        }

        $mission['events'][] = MissionEvent::make(
            'step.completed',
            'Ejecución finalizada',
            $step['title'],
            $step['agent'],
        )->toArray();

        return $mission;
    }

    /** @return array<string, string|null> */
    private function extractFacts(string $html, string $url): array
    {
        $title = $this->match($html, '/<title[^>]*>(.*?)<\/title>/si')
            ?? $this->match($html, '/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/si')
            ?? $this->match($html, '/<h1[^>]*>(.*?)<\/h1>/si');

        $description = $this->match($html, '/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/si')
            ?? $this->match($html, '/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/si');

        $language = $this->match($html, '/<html[^>]+lang=["\']([^"\']+)["\']/si');

        return [
            'url' => $url,
            'title' => $title ? trim(Str::limit($title, 200)) : null,
            'description' => $description ? trim(Str::limit($description, 500)) : null,
            'language' => $language ? Str::before($language, '-') : null,
        ];
    }

    private function match(string $html, string $pattern): ?string
    {
        if (preg_match($pattern, $html, $matches) !== 1) {
            return null;
        }

        return html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** @param  array<int, array<string, mixed>>  $steps */
    private function runningStepIndex(array $steps): ?int
    {
        foreach ($steps as $index => $candidate) {
            if (($candidate['status'] ?? null) === 'running') {
                return $index;
            }
        }

        return null;
    }
}
