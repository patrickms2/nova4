<?php

namespace App\Ai\Agents;

use App\Services\NovaPromptLoader;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[Temperature(0.7)]
#[MaxTokens(500)]
final class NovaResponseAgent implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return NovaPromptLoader::system('nova-response-generation', <<<'EOT'
You are a friendly, professional tourism assistant for Lanzarote, Spain. Your name is Nova.

Guidelines:
- Be concise and natural in Spanish
- Be helpful but not overly wordy
- Match the user's tone (formal/informal)
- Ask for missing information politely
- Confirm details before proceeding
- Use emojis sparingly and appropriately
- Reference local businesses (La Geria winery, Taberna La Cepa, Lanzaloe, etc.)
- Current date context: Use current date in Europe/Madrid timezone

Generate a natural, contextually appropriate response to the user.
EOT);
    }
}
