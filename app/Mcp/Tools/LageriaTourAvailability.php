<?php

namespace App\Mcp\Tools;

use App\Models\Tour;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Check tour availability for La Geria tours by date and participants')]
class LageriaTourAvailability extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $serviceId = $request->input('service_id');
        $date = $request->input('date');
        $participants = $request->input('participants', 1);

        if (! $serviceId || ! $date) {
            return Response::text(json_encode([
                'error' => 'service_id and date are required',
            ]));
        }

        try {
            $url = config('app.url').'/explore/availability';

            $response = Http::timeout(15)->get($url, [
                'type' => 'tour_visit',
                'service_id' => $serviceId,
                'date' => $date,
                'participants' => $participants,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Ensure we always return a JSON string
                if (is_array($data)) {
                    return Response::text(json_encode($data));
                }
                
                return Response::text((string) $data);
            }

            return Response::text(json_encode([
                'error' => 'Failed to check availability',
                'status' => $response->status(),
            ]));
        } catch (\Throwable $e) {
            return Response::text(json_encode([
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'service_id' => $schema->integer()->description('The tour service ID'),
            'date' => $schema->string()->description('The date in YYYY-MM-DD format'),
            'participants' => $schema->integer()->description('Number of participants (default: 1)'),
        ];
    }
}
