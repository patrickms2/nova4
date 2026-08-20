<?php

namespace App\Http\Controllers;

use App\Models\Tool;
use App\Services\GeoapifyService;
use App\Services\ToolExecutor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapController extends Controller
{
    protected $geoapify;

    public function __construct(GeoapifyService $geoapify, protected ToolExecutor $toolExecutor)
    {
        $this->geoapify = $geoapify;
    }

    /**
     * Show the address selection map
     */
    public function selectAddress()
    {
        $mapTilesKey = config('app.geoapify.map_tiles');
        $geocodingKey = config('app.geoapify.geocoding');

        return view('maps.select-address', compact('mapTilesKey', 'geocodingKey'));
    }

    /**
     * Show the taxi route tracking map
     */
    public function taxiRoute()
    {
        $mapTilesKey = config('app.geoapify.map_tiles');
        $routingKey = config('app.geoapify.routing');
        $geocodingKey = config('app.geoapify.geocoding');

        return view('maps.taxi-route', compact('mapTilesKey', 'routingKey', 'geocodingKey'));
    }

    /**
     * Search for places
     */
    public function searchPlaces(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|string',
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'radius' => 'nullable|numeric',
        ]);

        $radius = $validated['radius'] ?? 1000;

        $places = $this->geoapify->searchPlaces(
            $validated['categories'],
            $validated['lat'],
            $validated['lon'],
            $radius
        );

        return response()->json($places);
    }

    /**
     * Get route between two points
     */
    public function getRoute(Request $request)
    {
        $validated = $request->validate([
            'from_lat' => 'required|numeric',
            'from_lon' => 'required|numeric',
            'to_lat' => 'required|numeric',
            'to_lon' => 'required|numeric',
            'mode' => 'nullable|string',
        ]);

        $mode = $validated['mode'] ?? 'drive';

        $route = $this->geoapify->getRoute(
            $validated['from_lat'],
            $validated['from_lon'],
            $validated['to_lat'],
            $validated['to_lon'],
            $mode
        );

        return response()->json($route);
    }

    /**
     * Geocode an address
     */
    public function geocodeAddress(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string',
        ]);

        $result = $this->geoapify->geocodeAddress($validated['address']);

        return response()->json($result);
    }

    /**
     * Reverse geocode coordinates
     */
    public function reverseGeocode(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        $result = $this->geoapify->reverseGeocode($validated['lat'], $validated['lon']);

        return response()->json($result);
    }

    public function getPrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_location' => ['required', 'string', 'max:220'],
            'dropoff_location' => ['required', 'string', 'max:220'],
            'passengers' => ['nullable', 'integer', 'min:1', 'max:16'],
        ]);

        $data = $this->estimatePrice($validated['pickup_location'], $validated['dropoff_location'], $validated['passengers'] ?? 1);

        return response()->json([
            'data' => is_array($data) ? $data : ['result' => $data],
        ]);
    }

    private function estimatePrice(string $pickup, string $dropoff, int $passengers = 1): mixed
    {
        $tool = Tool::query()
            ->where('name', 'transfer_price_estimate')
            ->where('is_active', true)
            ->first();

        if (! $tool) {
            return null;
        }

        $result = $this->toolExecutor->execute($tool, [
            'pickup_location' => $pickup,
            'dropoff_location' => $dropoff,
            'passengers' => $passengers,
        ]);

        return is_string($result) ? json_decode($result, true) : $result;
    }

    /**
     * Get transfer route between pickup and dropoff locations.
     * Returns pickup/dropoff coords plus a GeoJSON LineString for Leaflet.
     */
    public function transferRoute(Request $request)
    {
        $validated = $request->validate([
            'pickup' => 'required|string',
            'dropoff' => 'required|string',
            'mode' => 'nullable|string|in:drive,walk',
        ]);

        $mode = $validated['mode'] ?? 'drive';

        // Bias geocoding towards Lanzarote (28.96, -13.65) with 30 km radius
        $lanzaroteBias = ['bias' => 'proximity:-13.648,28.963', 'filter' => 'countrycode:es'];

        $pickup = $this->geoapify->geocodeAddress($validated['pickup'], $lanzaroteBias);
        $dropoff = $this->geoapify->geocodeAddress($validated['dropoff'], $lanzaroteBias);

        if (! $pickup || ! $dropoff) {
            return response()->json([
                'error' => 'Could not geocode one or both addresses',
            ], 422);
        }

        $route = $this->geoapify->getRoute(
            $pickup['lat'],
            $pickup['lon'],
            $dropoff['lat'],
            $dropoff['lon'],
            $mode
        );

        $price = $this->estimatePrice($validated['pickup'], $validated['dropoff']);

        // Build a GeoJSON FeatureCollection for Leaflet's L.geoJSON()
        // Geoapify returns MultiLineString coordinates (array of legs)
        $geoJson = null;
        if ($route && ! empty($route['route'])) {
            $geoJson = [
                'type' => 'FeatureCollection',
                'features' => [[
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'MultiLineString',
                        'coordinates' => $route['route'],
                    ],
                    'properties' => [
                        'distance' => $route['distance'] ?? null,
                        'time' => $route['time'] ?? null,
                    ],
                ]],
            ];
        }

        return response()->json([
            'pickup' => array_merge($pickup, ['query' => $validated['pickup']]),
            'dropoff' => array_merge($dropoff, ['query' => $validated['dropoff']]),
            'route' => $geoJson,
            'meta' => [
                'distance' => $route['distance'] ?? null,
                'time' => $route['time'] ?? null,
                'price' => $price,
            ],
        ]);
    }
}
