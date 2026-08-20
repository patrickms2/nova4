<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaAiKnowledge;
use App\Models\NovaBusiness;

final class NovaKnowledgeService
{
    /**
     * @return array<int, array{title:string, content:string}>
     */
    public function relevantKnowledge(?NovaBusiness $business, string $message, int $limit = 5): array
    {
        if (! $business) {
            return [];
        }

        $terms = $this->terms($message);

        return NovaAiKnowledge::query()
            ->where('nova_business_id', $business->id)
            ->where('status', 'active')
            ->latest()
            ->limit(20)
            ->get(['title', 'content'])
            ->map(fn (NovaAiKnowledge $knowledge): array => [
                'title' => $knowledge->title,
                'content' => $knowledge->content,
                'score' => $this->score($knowledge->title.' '.$knowledge->content, $terms),
            ])
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
