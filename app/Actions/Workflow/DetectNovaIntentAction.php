<?php

namespace App\Actions\Workflow;

use App\Models\NovaIntentRule;
use App\Models\NovaIntentToServerMapping;

class DetectNovaIntentAction
{
    /**
     * Called by the workflow "Action" node.
     *
     * Expected payload keys:
     *   - message : string (mensaje del usuario)
     */
    public function __invoke(array $payload): array
    {
        $message = $payload['message'] ?? null;

        if (! $message) {
            return ['error' => 'message is required.'];
        }

        try {
            // Detectar intención usando las reglas de Filament
            $intent = $this->detectIntent($message);

            if (! $intent) {
                return [
                    'success' => true,
                    'intent' => 'general',
                    'server_slug' => null,
                    'tool_name' => null,
                    'confidence' => 0,
                ];
            }

            // Obtener mapeo de intención a server
            $mapping = NovaIntentToServerMapping::where('intent_key', $intent)
                ->where('is_active', true)
                ->first();

            if (! $mapping) {
                return [
                    'success' => true,
                    'intent' => $intent,
                    'server_slug' => null,
                    'tool_name' => null,
                    'confidence' => 0.5,
                    'message' => "Intent detected but no server mapping found",
                ];
            }

            return [
                'success' => true,
                'intent' => $intent,
                'server_slug' => $mapping->server->slug ?? null,
                'tool_name' => $mapping->tool_name ?? null,
                'confidence' => 0.8,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function detectIntent(string $message): ?string
    {
        // TODO: Implementar lógica de detección de intención usando nova_intent_rules
        // Por ahora, detección simple basada en palabras clave
        $message = strtolower($message);

        $intentRules = NovaIntentRule::where('is_active', true)->get();

        foreach ($intentRules as $rule) {
            if ($this->matchesRule($message, $rule)) {
                return $rule->intent_key;
            }
        }

        return null;
    }

    private function matchesRule(string $message, NovaIntentRule $rule): bool
    {
        // TODO: Implementar lógica de matching basada en las reglas de Filament
        // Por ahora, detección simple
        $keywords = $rule->keywords ?? [];
        foreach ($keywords as $keyword) {
            if (str_contains($message, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
