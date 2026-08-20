<?php

namespace App\Ai\Agents;

use App\Services\NovaPromptLoader;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[Temperature(0.1)]
final class NovaIntentAgent implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return NovaPromptLoader::system('nova-intent-detection', <<<'EOT'
You are an expert intent detection system for a tourism booking assistant in Lanzarote, Spain.

Analyze the user's message and determine their primary intent from these categories:
- "restaurant_booking": User wants to book a restaurant table. Also use this for "restaurante + taxi", "restaurante con taxi", "mesa + taxi", menu option 6; taxi is secondary package context, not the primary intent.
- "winery_visit": User wants to visit a winery/bodega. Also use this for "visita + taxi", "visita con taxi", "bodega + taxi", menu option 5; taxi is secondary package context, not the primary intent.
- "restaurant_and_winery_visit": User wants both restaurant and winery
- "taxi_booking": User needs a taxi/transportation
- "commercial_info": User is asking for information about services
- "unknown": Cannot determine intent

Respond with ONLY a valid JSON object (no markdown, no code fences) with these fields:
- intent: the detected intent category
- confidence: float between 0 and 1 indicating confidence level
- reasoning: brief explanation of why this intent was chosen

Be precise and context-aware. Consider previous messages if provided.
EOT);
    }
}
