<?php

namespace App\Actions\Workflow;

use App\Models\Server;
use Illuminate\Support\Facades\Log;

class NormalizeMcpResponseAction
{
    /**
     * Called by the workflow "Action" node.
     *
     * Expected payload keys:
     *   - raw_response : array (respuesta del MCP)
     *   - server_slug  : string (slug del server MCP)
     *   - response_type: booking | order | transaction
     */
    public function __invoke(array $payload): array
    {
        $rawResponse = $payload['raw_response'] ?? null;
        $serverSlug = $payload['server_slug'] ?? null;
        $responseType = $payload['response_type'] ?? 'booking';

        if (! $rawResponse || ! $serverSlug) {
            return ['error' => 'raw_response and server_slug are required.'];
        }

        $server = Server::where('slug', $serverSlug)
            ->where('is_active', true)
            ->first();

        if (! $server) {
            return ['error' => "Server [{$serverSlug}] not found or inactive."];
        }

        try {
            $normalized = $this->normalizeResponse($rawResponse, $server, $responseType);

            return [
                'success' => true,
                'normalized' => $normalized,
                'source' => $server->name,
                'response_type' => $responseType,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function normalizeResponse(array $rawResponse, Server $server, string $responseType): array
    {
        // TODO: Implementar lógica de normalización específica por tipo de MCP
        // Por ahora, retornamos la respuesta raw con estructura básica

        return match ($responseType) {
            'booking' => $this->normalizeBooking($rawResponse, $server),
            'order' => $this->normalizeOrder($rawResponse, $server),
            'transaction' => $this->normalizeTransaction($rawResponse, $server),
            'services' => $this->normalizeServices($rawResponse, $server),
            default => $rawResponse,
        };
    }

    private function normalizeBooking(array $rawResponse, Server $server): array
    {
        // Ejemplo para Taxilanz hotel_list
        if (isset($rawResponse['result']['body']['hoteles'])) {
            return [
                'source' => $server->name,
                'type' => 'booking',
                'data' => $rawResponse['result']['body']['hoteles'],
            ];
        }

        // Para La Geria availability (varios formatos posibles)
        $availabilityData = $this->extractAvailabilityData($rawResponse);
        if ($availabilityData !== null) {
            $formatted = $this->formatAvailabilityResponse($availabilityData);

            return [
                'source' => $server->name,
                'type' => 'booking',
                'data' => $availabilityData,
                'normalized' => $formatted['text'],
                'choices' => $formatted['choices'] ?? null,
            ];
        }

        return $rawResponse;
    }

    /**
     * Extrae el bloque de disponibilidad de distintos formatos de respuesta MCP.
     */
    private function extractAvailabilityData(array $rawResponse): ?array
    {
        $paths = [
            $rawResponse['data'] ?? null,
            $rawResponse['result'] ?? null,
            $rawResponse['result']['body'] ?? null,
            $rawResponse['result']['data'] ?? null,
            $rawResponse['body'] ?? null,
        ];

        foreach ($paths as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }

            if (isset($candidate['available_slots']) || isset($candidate['times']) || isset($candidate['slots'])) {
                return $candidate;
            }

            // Algunas respuestas anidan data -> times
            if (isset($candidate['data']['times']) || isset($candidate['data']['slots'])) {
                return $candidate['data'];
            }
        }

        return null;
    }

    private function formatAvailabilityResponse(array $data): array
    {
        // available_slots o slots
        foreach (['available_slots', 'slots'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $slots = $this->extractSlotTimes($data[$key]);

                if (empty($slots)) {
                    return [
                        'text' => "No hay horarios disponibles para esta fecha.",
                        'choices' => null,
                    ];
                }

                $formatted = collect($slots)->map(fn ($t) => "• {$t}")->implode("\n");

                return [
                    'text' => $formatted,
                    'choices' => collect($slots)->implode(','),
                ];
            }
        }

        // times plano o anidado en data
        $times = $data['times'] ?? $data['data']['times'] ?? null;
        if (is_array($times)) {
            $availableTimes = collect($times)
                ->filter(fn ($t) => is_array($t) ? ($t['available'] ?? true) === true : true)
                ->map(fn ($t) => is_array($t) ? ($t['time'] ?? $t['label'] ?? $t['value'] ?? null) : $t)
                ->filter()
                ->sort()
                ->values();

            if ($availableTimes->isEmpty()) {
                return [
                    'text' => "No hay horarios disponibles para esta fecha.",
                    'choices' => null,
                ];
            }

            $formatted = $availableTimes->map(fn ($t) => "• {$t}")->implode("\n");

            return [
                'text' => $formatted,
                'choices' => $availableTimes->implode(','),
            ];
        }

        if (isset($data['error'])) {
            return [
                'text' => "Error: " . $data['error'],
                'choices' => null,
            ];
        }

        return [
            'text' => json_encode($data),
            'choices' => null,
        ];
    }

    /**
     * @param  array<int, mixed>  $slots
     * @return array<int, string>
     */
    private function extractSlotTimes(array $slots): array
    {
        return collect($slots)
            ->map(static function (mixed $slot): ?string {
                if (is_string($slot)) {
                    return $slot;
                }

                if (is_array($slot)) {
                    return $slot['time'] ?? $slot['label'] ?? $slot['value'] ?? null;
                }

                return null;
            })
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    private function normalizeOrder(array $rawResponse, Server $server): array
    {
        // Ejemplo para Lanzaloe Magento orders
        if (isset($rawResponse['result']['body']['orders'])) {
            return [
                'source' => $server->name,
                'type' => 'order',
                'data' => $rawResponse['result']['body']['orders'],
            ];
        }

        return $rawResponse;
    }

    private function normalizeTransaction(array $rawResponse, Server $server): array
    {
        // Ejemplo para transacciones
        if (isset($rawResponse['result']['body']['transactions'])) {
            return [
                'source' => $server->name,
                'type' => 'transaction',
                'data' => $rawResponse['result']['body']['transactions'],
            ];
        }

        return $rawResponse;
    }

    private function normalizeServices(array $rawResponse, Server $server): array
    {
        // El MCP puede devolver services directamente o envueltos en distintas claves
        $services = $rawResponse['data']['services']
            ?? $rawResponse['result']['data']['services']
            ?? (isset($rawResponse['result']) && is_array($rawResponse['result']) && array_is_list($rawResponse['result'])
                ? $rawResponse['result']
                : null)
            ?? (isset($rawResponse['data']) && is_array($rawResponse['data']) && array_is_list($rawResponse['data'])
                ? $rawResponse['data']
                : null)
            ?? null;

        Log::debug('NormalizeMcpResponseAction normalizeServices', [
            'server' => $server->slug,
            'raw_response' => $rawResponse,
            'extracted_services' => $services,
        ]);

        // Para La Geria LatePoint services
        if (is_array($services)) {

            // Extraer choices para collectInput y services array para UI
            $choices = [];
            $serviceMap = [];
            $servicesArray = [];

            if (is_array($services)) {
                foreach ($services as $service) {
                    if (is_array($service)) {
                        $name = $service['name'] ?? $service['title'] ?? 'Unknown';
                        $id = $service['id'] ?? $service['service_id'] ?? null;
                        $status = $service['status'] ?? 'active';

                        // Solo incluir servicios activos
                        if ($status === 'active') {
                            $choices[] = $name;
                            if ($id) {
                                $serviceMap[$name] = $id;
                            }
                            // Guardar servicio completo para UI
                            $servicesArray[] = [
                                'id' => $id,
                                'name' => $name,
                                'description' => $service['description'] ?? '',
                                'price' => $service['price'] ?? null,
                                'duration' => $service['duration'] ?? null,
                            ];
                        }
                    } elseif (is_string($service)) {
                        $choices[] = $service;
                    }
                }
            }

            $normalized = [
                'source' => $server->name,
                'type' => 'services',
                'data' => $services,
                'choices' => implode(',', $choices),
                'service_map' => $serviceMap,
                'services' => $servicesArray,
            ];

            Log::debug('NormalizeMcpResponseAction normalizeServices result', $normalized);

            return $normalized;
        }

        Log::debug('NormalizeMcpResponseAction normalizeServices no services found', ['raw_response' => $rawResponse]);

        return $rawResponse;
    }
}
