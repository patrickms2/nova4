<?php

namespace App\Actions\Workflow;

use App\Models\NovaBusiness;
use App\Services\Nova\NovaKnowledgeService;

class SearchNovaKnowledgeAction
{
    public function __construct(private readonly NovaKnowledgeService $knowledge) {}

    /**
     * Called by the workflow "Action" node.
     *
     * Expected payload keys:
     *   - message          : the user query (required)
     *   - nova_business_id : scope to a specific business (optional)
     *   - business_slug    : alternative to nova_business_id (optional)
     *   - limit            : max results (default 5)
     */
    public function __invoke(array $payload): array
    {
        $message = $payload['message'] ?? '';
        $businessId = $payload['nova_business_id'] ?? null;
        $slug = $payload['business_slug'] ?? null;
        $limit = (int) ($payload['limit'] ?? 5);

        $business = null;

        if ($businessId) {
            $business = NovaBusiness::find($businessId);
        } elseif ($slug) {
            $business = NovaBusiness::where('slug', $slug)->first();
        }

        $results = $this->knowledge->relevantKnowledge($business, $message, $limit);

        return [
            'count' => count($results),
            'results' => collect($results)->map(fn (array $item) => [
                'title' => $item['title'] ?? '',
                'content' => $item['content'] ?? '',
                'score' => $item['score'] ?? 0,
            ])->values()->all(),
        ];
    }
}
