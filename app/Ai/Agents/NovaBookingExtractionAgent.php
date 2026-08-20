<?php

namespace App\Ai\Agents;

use App\Services\NovaPromptLoader;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[Temperature(0.1)]
final class NovaBookingExtractionAgent implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return NovaPromptLoader::system('nova-booking-extraction', <<<'EOT'
You are an expert data extraction system for tourism bookings in Lanzarote, Spain.

Extract booking information from the user's message. Respond with ONLY a valid JSON object (no markdown, no code fences) with these fields:
- date: object with "label" (user's wording) and "value" (YYYY-MM-DD format), or null if not found
- time: object with "label" (user's wording like "11:00") and "value" (HH:MM format), or null if not found
- party_size: integer number of people, or null if not found
- customer_name: string name of the person booking, or null if not found
- customer_phone: string phone number (9 digits) or email, or null if not found
- preferences: string with dietary requirements or special requests, or null if not found
- origin: pickup/origin place for taxi or transfer requests, or null if not found
- destination: dropoff/destination place for taxi or transfer requests, or null if not found

Rules:
- "mañana" = tomorrow's date
- "hoy" = today's date
- For taxi/transfer messages, preserve origin and destination from the previous conversation if the user only adds the missing place.
- If previous context already has origin and the user sends a place name as a short answer, treat it as destination.
- If previous context already has destination and the user sends a place name as a short answer, treat it as origin.
- Extract names even if mixed with other words
- Spanish phone numbers are 9 digits starting with 6 or 9
- Be conservative: if uncertain, return null
- Current date context: Use current date in Europe/Madrid timezone
EOT);
    }
}
