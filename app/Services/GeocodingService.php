<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    private string $baseUrl;
    private int $cacheMinutes;

    public function __construct()
    {
        $this->baseUrl = 'https://nominatim.openstreetmap.org/reverse';
        $this->cacheMinutes = 60 * 24; // Cache por 24 horas
    }

    /**
     * Obtiene la dirección legible de unas coordenadas
     */
    public function getAddressFromCoordinates(float $latitude, float $longitude): ?string
    {
        try {
            // Crear clave única para cache
            $cacheKey = "geocoding_" . number_format($latitude, 4) . "_" . number_format($longitude, 4);
            
            // Verificar cache primero
            if (Cache::has($cacheKey)) {
                Log::info("GeocodingService: Cache hit for coordinates: {$latitude}, {$longitude}");
                return Cache::get($cacheKey);
            }
            
            Log::info("GeocodingService: Making API request for coordinates: {$latitude}, {$longitude}");
            
            // Llamada a Nominatim de OpenStreetMap con timeout reducido
            $response = Http::timeout(2) // Reducido de 5 a 2 segundos
                ->retry(1, 100) // Reintentar 1 vez con 100ms de espera
                ->withHeaders([
                    'User-Agent' => 'TraccarApp/1.0 (Laravel GPS Tracking)'
                ])
                ->get($this->baseUrl, [
                    'format' => 'json',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 16, // Reducido de 18 a 16 para respuestas más rápidas
                    'addressdetails' => 1,
                    'accept-language' => 'es,en'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['display_name'])) {
                    $address = $this->formatAddress($data);
                    
                    // Guardar en cache
                    Cache::put($cacheKey, $address, $this->cacheMinutes * 60);
                    
                    Log::info("GeocodingService: Address found and cached: {$address}");
                    return $address;
                }
            }
            
            Log::warning("GeocodingService: No address found for coordinates: {$latitude}, {$longitude}");
            
            // Cache null result para evitar llamadas repetidas
            Cache::put($cacheKey, null, 15); // Solo 15 minutos para nulls
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("GeocodingService: Exception getting address: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Formatea la respuesta de Nominatim a una dirección legible
     */
    private function formatAddress(array $data): string
    {
        $address = $data['address'] ?? [];
        $parts = [];

        // Construir dirección de forma inteligente
        if (isset($address['road'])) {
            $road = $address['road'];
            if (isset($address['house_number'])) {
                $road = $address['road'] . ' ' . $address['house_number'];
            }
            $parts[] = $road;
        }

        // Agregar barrio o colonia
        if (isset($address['neighbourhood'])) {
            $parts[] = $address['neighbourhood'];
        } elseif (isset($address['suburb'])) {
            $parts[] = $address['suburb'];
        } elseif (isset($address['quarter'])) {
            $parts[] = $address['quarter'];
        }

        // Agregar ciudad
        if (isset($address['city'])) {
            $parts[] = $address['city'];
        } elseif (isset($address['town'])) {
            $parts[] = $address['town'];
        } elseif (isset($address['municipality'])) {
            $parts[] = $address['municipality'];
        }

        // Agregar estado/región
        if (isset($address['state'])) {
            $parts[] = $address['state'];
        }

        // Agregar país
        if (isset($address['country'])) {
            $parts[] = $address['country'];
        }

        // Si no pudimos construir dirección, usar display_name
        if (empty($parts)) {
            return $data['display_name'] ?? 'Dirección no disponible';
        }

        return implode(', ', array_slice($parts, 0, 4)); // Máximo 4 partes para no ser muy largo
    }

    /**
     * Obtiene múltiples direcciones de una vez (para eficiencia)
     */
    public function getMultipleAddresses(array $coordinates): array
    {
        $addresses = [];
        $maxRequests = 20; // Límite máximo de peticiones por llamada
        $requestCount = 0;
        
        Log::info("GeocodingService: Processing " . count($coordinates) . " coordinates (max: {$maxRequests})");
        
        foreach ($coordinates as $coord) {
            if ($requestCount >= $maxRequests) {
                Log::info("GeocodingService: Reached maximum requests limit ({$maxRequests}), stopping");
                break;
            }
            
            if (isset($coord['latitude']) && isset($coord['longitude'])) {
                $key = $coord['latitude'] . ',' . $coord['longitude'];
                
                // Verificar cache primero para no contar como request
                $cacheKey = "geocoding_" . number_format($coord['latitude'], 4) . "_" . number_format($coord['longitude'], 4);
                if (Cache::has($cacheKey)) {
                    $addresses[$key] = Cache::get($cacheKey);
                    continue;
                }
                
                // Solo hacer request si no está en cache
                $addresses[$key] = $this->getAddressFromCoordinates(
                    $coord['latitude'], 
                    $coord['longitude']
                );
                $requestCount++;
                
                // Pequeña pausa entre requests para ser respetuosos con la API
                if ($requestCount < $maxRequests) {
                    usleep(100000); // 100ms de pausa
                }
            }
        }
        
        Log::info("GeocodingService: Completed {$requestCount} requests, returned " . count($addresses) . " addresses");
        return $addresses;
    }

    /**
     * Limpia el cache de geocodificación (para mantenimiento)
     */
    public function clearCache(): void
    {
        $pattern = 'geocoding_*';
        // Nota: Laravel no tiene método nativo para limpiar por patrón
        // En producción se podría usar Redis SCAN o implementar tracking de claves
        Log::info("GeocodingService: Cache clear requested (implement based on cache driver)");
    }
}