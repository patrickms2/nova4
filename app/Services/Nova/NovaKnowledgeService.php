<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaAiKnowledge;
use App\Models\NovaBusiness;
use Illuminate\Support\Collection;

final class NovaKnowledgeService
{
    public function __construct(private readonly NovaKnowledgeEmbedder $embedder) {}

    /**
     * @return array<int, array{title:string, content:string}>
     */
    public function relevantKnowledge(?NovaBusiness $business, string $message, int $limit = 5): array
    {
        if (! $business) {
            return [];
        }

        $records = NovaAiKnowledge::query()
            ->where('nova_business_id', $business->id)
            ->where('status', 'active')
            ->latest()
            ->limit(50)
            ->get(['title', 'content', 'embedding']);

        if ($records->isEmpty()) {
            return [];
        }

        $queryVector = $this->embedder->embed($message, cache: true);

        $scored = $queryVector !== null
            ? $this->scoreBySimilarity($records, $queryVector)
            : $this->scoreByKeywords($records, $message);

        return $scored
            ->sortByDesc('score')
            ->take($limit)
            ->map(fn (array $knowledge): array => [
                'title' => $knowledge['title'],
                'content' => $knowledge['content'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, NovaAiKnowledge>  $records
     * @param  array<int, float>  $queryVector
     * @return Collection<int, array{title:string, content:string, score:float}>
     */
    private function scoreBySimilarity(Collection $records, array $queryVector): Collection
    {
        return $records->map(fn (NovaAiKnowledge $knowledge): array => [
            'title' => $knowledge->title,
            'content' => $knowledge->content,
            'score' => is_array($knowledge->embedding) && $knowledge->embedding !== []
                ? $this->cosineSimilarity($queryVector, $knowledge->embedding)
                : 0.0,
        ]);
    }

    /**
     * @param  Collection<int, NovaAiKnowledge>  $records
     * @return Collection<int, array{title:string, content:string, score:float}>
     */
    private function scoreByKeywords(Collection $records, string $message): Collection
    {
        $terms = $this->terms($message);

        return $records->map(fn (NovaAiKnowledge $knowledge): array => [
            'title' => $knowledge->title,
            'content' => $knowledge->content,
            'score' => (float) $this->score($knowledge->title.' '.$knowledge->content, $terms),
        ]);
    }

    /**
     * Cosine similarity between two equal-length vectors.
     *
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $length = min(count($a), count($b));

        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $valueA = (float) $a[$i];
            $valueB = (float) $b[$i];

            $dot += $valueA * $valueB;
            $magnitudeA += $valueA ** 2;
            $magnitudeB += $valueB ** 2;
        }

        if ($magnitudeA <= 0.0 || $magnitudeB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($magnitudeA) * sqrt($magnitudeB));
    }

    /**
     * @return array<int, string>
     */
    private function terms(string $message): array
    {
        $normalizedMessage = mb_strtolower($message);
        $words = preg_split('/\W+/u', $normalizedMessage) ?: [];

        return array_values(array_filter($words, fn (string $word): bool => mb_strlen($word) >= 4));
    }

    /**
     * @param  array<int, string>  $terms
     */
    private function score(string $content, array $terms): int
    {
        $normalizedContent = mb_strtolower($content);
        $score = 0;

        foreach ($terms as $term) {
            if (str_contains($normalizedContent, $term)) {
                $score++;
            }
        }

        return $score;
    }
}
