<?php

namespace App\Actions\Workflow;

use App\Models\NovaBusiness;
use App\Models\NovaCrossSellingRule;

class ApplyNovaCrossSellingAction
{
    /**
     * Called by the workflow "Action" node.
     *
     * Expected payload keys:
     *   - business_slug : string (slug del negocio actual)
     *   - intent        : string (intent detectado)
     */
    public function __invoke(array $payload): array
    {
        $businessSlug = $payload['business_slug'] ?? null;
        $intent = $payload['intent'] ?? null;

        if (! $businessSlug || ! $intent) {
            return ['error' => 'business_slug and intent are required.'];
        }

        try {
            $business = NovaBusiness::where('slug', $businessSlug)->first();

            if (! $business) {
                return ['error' => "Business not found: {$businessSlug}"];
            }

            $rule = NovaCrossSellingRule::active()
                ->forBusiness($business->id)
                ->forIntent($intent)
                ->first();

            if (! $rule) {
                return [
                    'success' => true,
                    'cross_selling_applied' => false,
                    'message' => "No cross-selling rule found for intent: {$intent}",
                ];
            }

            return [
                'success' => true,
                'cross_selling_applied' => true,
                'rule' => [
                    'from_business' => $rule->fromBusiness->name ?? null,
                    'to_business' => $rule->toBusiness->name ?? null,
                    'trigger_intent' => $rule->trigger_intent,
                    'message' => $rule->message,
                    'cta_label' => $rule->cta_label,
                    'cta_url' => $rule->cta_url,
                    'priority' => $rule->priority,
                ],
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
