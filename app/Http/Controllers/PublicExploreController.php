<?php

namespace App\Http\Controllers;

use App\Actions\Booking\CreatePackageBookingRequest;
use App\Models\ExternalBooking;
use App\Models\ExternalCatalogItem;
use App\Models\ExternalSyncMapping;
use App\Models\Hotel;
use App\Models\Server;
use App\Models\PublicBookingRequest;
use App\Models\Restaurant;
use App\Models\TaxiService;
use App\Models\Tool;
use App\Models\Tour;
use App\Services\ExternalSync\RemoteBookingCreator;
use App\Services\Nova\SirvoReservationClient;
use App\Services\PublicBookingRequestAssigner;
use App\Services\ToolExecutor;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicExploreController extends Controller
{
    public function __construct(
        private readonly PublicBookingRequestAssigner $assigner,
        private readonly RemoteBookingCreator $remoteBookingCreator,
        private readonly SirvoReservationClient $sirvoClient,
    ) {}

    public function index(): View
    {
        return view('public.explore');
    }

    public function places(): JsonResponse
    {
        $places = collect()
            ->merge($this->hotels())
            ->merge($this->restaurants())
            ->merge($this->taxiServices())
            ->merge($this->tours())
            ->merge($this->transfers())
            ->values();

        return response()->json([
            'data' => $places,
            'meta' => [
                'total' => $places->count(),
                'types' => [
                    'hotel' => $places->where('type', 'hotel')->count(),
                    'restaurant' => $places->where('type', 'restaurant')->count(),
                    'taxi' => $places->where('type', 'taxi')->count(),
                    'tour_visit' => $places->where('type', 'tour_visit')->count(),
                    'taxi_route' => $places->where('type', 'taxi_route')->count(),
                    'transfer' => $places->where('type', 'transfer')->count(),
                ],
                'mappable' => [
                    'hotel' => $places->where('type', 'hotel')->where('has_coordinates', true)->count(),
                    'restaurant' => $places->where('type', 'restaurant')->where('has_coordinates', true)->count(),
                    'taxi' => $places->where('type', 'taxi')->where('has_coordinates', true)->count(),
                    'tour_visit' => $places->where('type', 'tour_visit')->where('has_coordinates', true)->count(),
                    'taxi_route' => $places->where('type', 'taxi_route')->where('has_coordinates', true)->count(),
                    'transfer' => $places->where('type', 'transfer')->where('has_coordinates', true)->count(),
                ],
            ],
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:tour_visit,taxi_route,restaurant,hotel,taxi,tour'],
            'service_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'participants' => ['nullable', 'integer', 'min:1', 'max:50'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:50'],
            'children' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);

        $bookingType = $this->bookingType($validated['type']);

        if ($bookingType !== 'tour') {
            return response()->json([
                'data' => [
                    'times' => [],
                    'source' => null,
                ],
            ]);
        }

        $tour = Tour::query()->where('is_active', true)->findOrFail((int) $validated['service_id']);

        $mapping = $tour->externalSyncMappings()
            ->with('source.server')
            ->latest('last_synced_at')
            ->first();

        $times = $this->defaultTourTimes($mapping?->source?->settings, $mapping?->source?->server?->metadata);
        $date = (string) $validated['date'];
        $capacity = $this->effectiveTourCapacity($tour);
        $participants = $this->participantsFromInputs($validated);
        $externalServiceId = $mapping?->external_id;

        $remoteBookedByTime = $this->latepointRemoteBookedParticipantsByTime($mapping?->source?->server?->metadata, $externalServiceId, $date);

        $availability = collect($times)->map(function (string $time) use ($date, $capacity, $participants, $externalServiceId, $remoteBookedByTime): array {
            $startsAt = $date.' '.$time.':00';
            $bookedParticipants = 0;

            if ($externalServiceId) {
                $bookedParticipants += $this->latepointLocalBookedParticipants($externalServiceId, $startsAt);
            }

            if (is_array($remoteBookedByTime) && isset($remoteBookedByTime[$time])) {
                $bookedParticipants += (int) $remoteBookedByTime[$time];
            }

            return [
                'time' => $time,
                'available' => ($bookedParticipants + $participants) <= $capacity,
            ];
        })->values();

        return response()->json([
            'data' => [
                'times' => $availability,
                'source' => [
                    'source_label' => $mapping?->source?->source_label ?? $mapping?->source_label,
                    'resource_type' => $mapping?->resource_type,
                ],
            ],
        ]);
    }

    public function transferEstimate(Request $request, ToolExecutor $toolExecutor): JsonResponse
    {
        $validated = $request->validate([
            'pickup_location' => ['required', 'string', 'max:220'],
            'dropoff_location' => ['required', 'string', 'max:220'],
            'passengers' => ['nullable', 'integer', 'min:1', 'max:16'],
        ]);

        $tool = Tool::query()
            ->where('name', 'transfer_price_estimate')
            ->where('is_active', true)
            ->firstOrFail();

        $result = $toolExecutor->execute($tool, $validated);
        $data = is_string($result) ? json_decode($result, true) : $result;

        return response()->json([
            'data' => is_array($data) ? $data : ['result' => $result],
        ]);
    }

    public function storePackageBookingRequest(Request $request, ToolExecutor $toolExecutor): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:160'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'visit.tour_id' => ['required', 'integer'],
            'visit.adults' => ['required', 'integer', 'min:1', 'max:50'],
            'visit.children' => ['nullable', 'integer', 'min:0', 'max:50'],
            'visit.tour_date' => ['required', 'date', 'after_or_equal:today'],
            'visit.tour_schedule' => ['required', 'date_format:H:i'],
            'visit.unit_price' => ['required', 'numeric', 'min:0'],
            'transfer.pickup' => ['required', 'string', 'max:220'],
            'transfer.dropoff' => ['required', 'string', 'max:220'],
            'transfer.pickup_at' => ['required', 'date', 'after_or_equal:now'],
            'transfer.passengers' => ['required', 'integer', 'min:1', 'max:16'],
        ]);

        $tour = Tour::query()->findOrFail((int) $validated['visit']['tour_id']);
        $adults = (int) $validated['visit']['adults'];
        $visitSubtotal = round((float) $validated['visit']['unit_price'] * $adults, 2);

        $transferTool = Tool::query()->where('name', 'transfer_price_estimate')->where('is_active', true)->first();
        $transferPrice = 0.0;

        if ($transferTool) {
            $priceResult = $toolExecutor->execute($transferTool, [
                'pickup_location' => $validated['transfer']['pickup'],
                'dropoff_location' => $validated['transfer']['dropoff'],
                'passengers' => $validated['transfer']['passengers'],
            ]);

            $priceData = is_string($priceResult) ? json_decode($priceResult, true) : $priceResult;
            $transferPrice = (float) ($priceData['estimated_price'] ?? 0);
        }

        $package = (new CreatePackageBookingRequest)->handle([
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'discount_percent' => $validated['discount_percent'] ?? 10,
            'items' => [
                [
                    'item_type' => 'tour_visit',
                    'service_id' => $tour->id,
                    'service_name' => $tour->name,
                    'quantity' => $adults,
                    'unit_price' => (float) $validated['visit']['unit_price'],
                    'subtotal' => $visitSubtotal,
                    'starts_at' => $validated['visit']['tour_date'].' '.$validated['visit']['tour_schedule'].':00',
                    'metadata' => ['children' => (int) ($validated['visit']['children'] ?? 0)],
                ],
                [
                    'item_type' => 'transfer',
                    'service_id' => null,
                    'service_name' => 'Transfer '.$validated['transfer']['pickup'].' → '.$validated['transfer']['dropoff'],
                    'quantity' => (int) $validated['transfer']['passengers'],
                    'unit_price' => $transferPrice,
                    'subtotal' => $transferPrice,
                    'starts_at' => $validated['transfer']['pickup_at'],
                    'metadata' => [
                        'origin' => $validated['transfer']['pickup'],
                        'destination' => $validated['transfer']['dropoff'],
                        'passengers' => (int) $validated['transfer']['passengers'],
                    ],
                ],
            ],
        ]);

        return response()->json([
            'data' => [
                'request_id' => $package->id,
                'reference' => $package->request_reference,
                'amount_cents' => $package->payment_amount_cents,
                'amount_label' => sprintf('%.2f€', ($package->payment_amount_cents ?? 0) / 100),
                'pay_url' => route('public.redsys.start', ['request' => $package->id]),
            ],
        ], 201);
    }

    public function storeBookingRequest(Request $request): JsonResponse
    {
        $base = $request->validate([
            'type' => ['required', 'in:hotel,taxi,restaurant,tour,tour_visit,taxi_route,tour_route,transfer'],
            'service_id' => ['required', 'integer'],
        ]);

        $bookingType = $this->bookingType($base['type']);
        $storedBookingType = in_array($base['type'], ['taxi_route', 'tour_route', 'transfer'], true) ? 'transfer' : $bookingType;
        $usesTourFlow = in_array($storedBookingType, ['tour', 'transfer'], true);
        $usesRestaurantFlow = $storedBookingType === 'restaurant';
        $service = $this->findService($bookingType, (int) $base['service_id']);
        $validated = $request->validate($this->bookingRules($bookingType, $base['type']));
        $validated = $this->normalizeTransferRequest($validated, $base['type']);
        $assignment = $this->assigner->resolve($bookingType, $service);
        $existingBookingRequest = $this->findRecentMatchingBookingRequest($storedBookingType, $service, $validated, $base['type']);

        if ($existingBookingRequest) {
            if (
                in_array($base['type'], ['taxi_route', 'tour_route'], true)
                && in_array($existingBookingRequest->remote_booking_status, ['skipped', 'failed', null], true)
            ) {
                $remoteBooking = $this->remoteBookingCreator->create($existingBookingRequest, $service);
                $existingBookingRequest->refresh();
            }

            // Materialize restaurant bookings when reusing existing request
            if ($storedBookingType === 'restaurant' && ! $existingBookingRequest->booking()->exists()) {
                $existingBookingRequest->materializeAsBooking();
            }

            $existingPayment = $existingBookingRequest->payment_provider === 'redsys' && $existingBookingRequest->payment_status !== 'paid'
                ? [
                    'amount_cents' => (int) ($existingBookingRequest->payment_amount_cents ?? 0),
                    'amount_label' => sprintf('%.2f€', ((int) ($existingBookingRequest->payment_amount_cents ?? 0)) / 100),
                    'pay_url' => route('public.redsys.start', ['request' => $existingBookingRequest->id]),
                ]
                : null;

            return response()->json([
                'message' => 'Request already exists. Reusing the existing booking request.',
                'data' => [
                    'request_id' => $existingBookingRequest->id,
                    'reference' => $existingBookingRequest->request_reference,
                    'status' => $existingBookingRequest->status,
                    'assigned_to' => $existingBookingRequest->assignedAdmin?->name,
                    'assignment_source' => $existingBookingRequest->assignment_source,
                    'remote_booking' => [
                        'status' => $existingBookingRequest->remote_booking_status,
                        'source_platform' => $existingBookingRequest->remote_source_platform,
                        'source_label' => $existingBookingRequest->remote_source_label,
                        'external_id' => $existingBookingRequest->remote_external_id,
                        'error' => $existingBookingRequest->remote_error,
                    ],
                    'amount_cents' => (int) ($existingBookingRequest->payment_amount_cents ?? 0),
                    'payment' => $existingPayment,
                    'warnings' => [],
                ],
            ]);
        }

        if ($usesTourFlow) {
            /** @var Tour $service */
            $mapping = $service->externalSyncMappings()
                ->with('source.server')
                ->latest('last_synced_at')
                ->first();

            $date = (string) ($validated['tour_date'] ?? '');
            $time = (string) ($validated['tour_schedule'] ?? '');
            $participants = $this->participantsFromInputs($validated);
            $basePrice = (float) ($service->base_price ?? 0.0);
            $capacity = $this->effectiveTourCapacity($service);
            $oversize = $participants > $capacity;

            // If the party exceeds capacity, we still accept the request (manual handling),
            // but we don't block the user with a 422 nor attempt remote creation.
            if (! $oversize && $date !== '' && $time !== '' && ! $this->isTourSlotAvailable($service, $mapping?->source?->server?->metadata, $mapping?->external_id, $date, $time, $participants)) {
                return response()->json([
                    'message' => 'Selected time slot is no longer available. Please pick another time.',
                    'errors' => [
                        'tour_schedule' => ['Selected time slot is no longer available.'],
                    ],
                ], 422);
            }
        }

        if ($usesRestaurantFlow) {
            /** @var Restaurant $service */
            $date = (string) ($validated['reservation_date'] ?? '');
            $time = (string) ($validated['reservation_time'] ?? '');
            $guests = (int) ($validated['guests'] ?? 1);
            $basePrice = (float) ($service->base_price ?? 0.0);

            if ($date !== '' && $time !== '' && ! $this->isRestaurantSlotAvailable($service, $date, $time, $guests)) {
                return response()->json([
                    'message' => 'Selected time slot is no longer available. Please pick another time.',
                    'errors' => [
                        'reservation_time' => ['Selected time slot is no longer available.'],
                    ],
                ], 422);
            }
        }

        $bookingRequest = PublicBookingRequest::create([
            'request_reference' => $this->requestReference(),
            'type' => $storedBookingType,
            'booking_kind' => in_array($base['type'], ['taxi_route', 'tour_route', 'transfer'], true) ? $base['type'] : null,
            'service_id' => $service->getKey(),
            'service_name' => $this->serviceName($service),
            'assigned_admin_id' => $assignment['admin']?->id,
            'assignment_source' => $assignment['source'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'status' => 'pending',
            'guests' => $validated['guests'] ?? null,
            'rooms' => $validated['rooms'] ?? null,
            'passengers' => $validated['passengers'] ?? null,
            'adults' => $validated['adults'] ?? null,
            'children' => $validated['children'] ?? null,
            'participants' => $this->participantsFromInputs($validated, true),
            'check_in_date' => $validated['check_in_date'] ?? null,
            'check_out_date' => $validated['check_out_date'] ?? null,
            'reservation_date' => $validated['reservation_date'] ?? null,
            'reservation_time' => $validated['reservation_time'] ?? null,
            'pickup_date_time' => $validated['pickup_date_time'] ?? null,
            'tour_date' => $validated['tour_date'] ?? null,
            'tour_schedule' => $validated['tour_schedule'] ?? null,
            'base_price' => $validated['base_price'] ?? $basePrice,
            'pickup_address' => $validated['pickup_address'] ?? null,
            'dropoff_address' => $validated['dropoff_address'] ?? null,
            'notes' => $this->normalizeTourNotes($bookingType, $validated),
        ]);
        $remoteBooking = null;
        if ($usesTourFlow) {
            $capacity = $this->effectiveTourCapacity($service);
            $participants = max(1, (int) ($bookingRequest->participants ?? 1));
            $oversize = $participants > $capacity;
            $basePrice = $bookingRequest->base_price ?? $service->base_price;

            if ($oversize) {
                $remoteBooking = [
                    'status' => 'skipped',
                    'source_platform' => null,
                    'source_label' => null,
                    'external_id' => null,
                    'error' => 'Party size exceeds max capacity ('.$capacity.'). Manual confirmation required.',
                ];

                $bookingRequest->forceFill([
                    'remote_booking_status' => 'skipped',
                    'remote_source_platform' => null,
                    'remote_source_label' => null,
                    'remote_external_id' => null,
                    'remote_response' => null,
                    'remote_error' => $remoteBooking['error'],
                ])->save();
            } else {
                $remoteBooking = $this->remoteBookingCreator->create($bookingRequest, $service);
            }
        } else {
            $remoteBooking = $this->remoteBookingCreator->create($bookingRequest, $service);
        }
        $unitEur = 0;
        $amountCents = 0;
        $unit = 0;
        $payment = null;
        if ($usesTourFlow) {
            $adults = max(1, (int) ($bookingRequest->adults ?? 1));
            $sourcePlatform = $remoteBooking['source_platform'] ?? $service->externalSyncMappings()
                ->latest('last_synced_at')
                ->value('source_platform');

            if ($sourcePlatform === 'woo') {
                $unitEur = $base['type'] === 'transfer'
                    ? (float) ($bookingRequest->base_price ?? 0)
                    : (float) ($service->base_price ?? 0);
                $unit = (int) round($unitEur * 100);
                $amountCents = max(0, $unit);
            } else {
                $unitEur = $this->latepointUnitPriceEurForTour($service) ?? 15.0;
                $unit = (int) round($unitEur * 100);
                $amountCents = max(0, $unit * $adults);
            }

            if (($remoteBooking['status'] ?? null) === 'created' && ($remoteBooking['source_platform'] ?? null) === 'latepoint') {
                $this->remoteBookingCreator->materializeLatepointRequest($bookingRequest->refresh());
            }

            if (($remoteBooking['status'] ?? null) === 'created') {
                $bookingRequest->forceFill([
                    'base_price' => $unitEur,
                    'payment_provider' => 'redsys',
                    'payment_status' => 'pending',
                    'payment_amount_cents' => $amountCents,
                ])->save();

                $payment = [
                    'amount_cents' => $amountCents,
                    'amount_label' => sprintf('%.2f€', $amountCents / 100),
                    'pay_url' => route('public.redsys.start', ['request' => $bookingRequest->id]),
                ];
            }
        }

        if ($usesRestaurantFlow) {
            // Materialize as Booking and RestaurantBooking for restaurants
            $bookingRequest->materializeAsBooking();
        }

        return response()->json([
            'message' => 'Request sent. The assigned manager can approve or cancel it from the panel.',
            'data' => [
                'request_id' => $bookingRequest->id,
                'reference' => $bookingRequest->request_reference,
                'status' => $bookingRequest->status,
                'assigned_to' => $assignment['admin']?->name,
                'assignment_source' => $assignment['source'],
                'remote_booking' => $remoteBooking,
                'amount_cents' => $amountCents,
                'payment' => $payment,
                'warnings' => ($bookingType === 'tour' && ($remoteBooking['status'] ?? null) === 'skipped')
                    ? [$remoteBooking['error'] ?? 'Manual confirmation required.']
                    : [],
            ],
        ], 201);
    }

    private function latepointUnitPriceEurForTour(Tour $tour): ?float
    {
        $mapping = $tour->externalSyncMappings()
            ->latest('last_synced_at')
            ->latest('id')
            ->first();

        if (! $mapping) {
            return null;
        }

        $item = ExternalCatalogItem::query()
            ->where('external_source_id', $mapping->external_source_id)
            ->where('external_id', (string) $mapping->external_id)
            ->latest('id')
            ->first();

        $raw = data_get($item?->metadata, 'raw', []);

        $value = data_get($raw, 'price');
        if ($value === null || $value === '') {
            $value = data_get($raw, 'charge_amount');
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function effectiveTourCapacity(Tour $tour): int
    {
        $capacity = max(1, (int) ($tour->max_capacity ?? 1));

        // If the tour is mapped to LatePoint, prefer the synced capacity_max.
        $mapping = $tour->externalSyncMappings()
            ->latest('last_synced_at')
            ->latest('id')
            ->first();

        if (! $mapping) {
            return $capacity;
        }

        $item = ExternalCatalogItem::query()
            ->where('external_source_id', $mapping->external_source_id)
            ->where('external_id', (string) $mapping->external_id)
            ->latest('id')
            ->first();

        $raw = data_get($item?->metadata, 'raw', []);
        $externalCapacity = (int) (data_get($raw, 'capacity_max') ?? 0);

        return max($capacity, $externalCapacity > 0 ? $externalCapacity : 1);
    }

    private function hotels(): Collection
    {
        return Hotel::query()
            ->with(['location.city.country', 'roomTypes'])
            ->where('is_active', true)
            ->get()
            ->map(fn (Hotel $hotel) => [
                'id' => 'hotel-'.$hotel->getKey(),
                'model_id' => $hotel->getKey(),
                'type' => 'hotel',
                'label' => 'Hotel',
                'name' => $hotel->name,
                'description' => $hotel->description,
                'latitude' => $this->nullableCoordinate($hotel->location?->latitude),
                'longitude' => $this->nullableCoordinate($hotel->location?->longitude),
                'has_coordinates' => $this->hasCoordinates($hotel->location),
                'address' => $this->address($hotel->location),
                'image' => $this->image($hotel->main_image_url, 'hotel'),
                'rating' => $this->rating($hotel->average_rating),
                'phone' => $hotel->phone,
                'email' => $hotel->email,
                'website' => $hotel->website,
                'featured' => (bool) $hotel->is_featured,
                'summary' => [
                    'Stars' => $hotel->star_rating ? $hotel->star_rating.' stars' : 'Hotel stay',
                    'Rooms' => $hotel->roomTypes->where('is_active', true)->count().' types',
                    'From' => $this->money($hotel->roomTypes->where('is_active', true)->min('base_price')),
                ],
            ] + $this->sourceMetadata('hotel', $hotel->getKey()));
    }

    private function restaurants(): Collection
    {
        return Restaurant::query()
            ->with(['location.city.country', 'tables'])
            ->where('is_active', true)
            ->get()
            ->map(fn (Restaurant $restaurant) => [
                'id' => 'restaurant-'.$restaurant->getKey(),
                'model_id' => $restaurant->getKey(),
                'type' => 'restaurant',
                'label' => 'Restaurant',
                'name' => $this->serviceName($restaurant),
                'description' => $restaurant->description,
                'latitude' => $this->nullableCoordinate($restaurant->location?->latitude),
                'longitude' => $this->nullableCoordinate($restaurant->location?->longitude),
                'has_coordinates' => $this->hasCoordinates($restaurant->location),
                'address' => $this->address($restaurant->location),
                'image' => $this->image($restaurant->main_image_url, 'restaurant'),
                'rating' => $this->rating($restaurant->average_rating),
                'phone' => $restaurant->phone,
                'email' => $restaurant->email,
                'website' => $restaurant->website,
                'featured' => (bool) $restaurant->is_featured,
                'summary' => [
                    'Cuisine' => $restaurant->cuisine ?: 'Local dining',
                    'Price' => $restaurant->price_range ?: 'Flexible',
                    'Tables' => $restaurant->tables->where('is_active', true)->count().' available',
                ],
            ] + $this->sourceMetadata('restaurant', $restaurant->getKey()));
    }

    private function taxiServices(): Collection
    {
        return TaxiService::query()
            ->with(['location.city.country', 'vehicleTypes', 'vehicles'])
            ->active()
            ->get()
            ->map(fn (TaxiService $taxiService) => [
                'id' => 'taxi-'.$taxiService->getKey(),
                'model_id' => $taxiService->getKey(),
                'type' => 'taxi',
                'label' => 'Taxi',
                'name' => $taxiService->name,
                'description' => $taxiService->description,
                'latitude' => $this->nullableCoordinate($taxiService->location?->latitude),
                'longitude' => $this->nullableCoordinate($taxiService->location?->longitude),
                'has_coordinates' => $this->hasCoordinates($taxiService->location),
                'address' => $this->address($taxiService->location),
                'image' => $this->image($taxiService->logo_url, 'taxi'),
                'rating' => $this->rating($taxiService->average_rating),
                'phone' => $taxiService->phone,
                'email' => $taxiService->email,
                'website' => $taxiService->website,
                'featured' => false,
                'summary' => [
                    'Fleet' => $taxiService->vehicles->where('is_active', true)->count().' vehicles',
                    'Options' => $taxiService->vehicleTypes->where('is_active', true)->count().' classes',
                    'Base' => $this->money($taxiService->vehicleTypes->where('is_active', true)->min('base_price')),
                ],
            ] + $this->sourceMetadata('taxi_service', $taxiService->getKey()));
    }

    private function tours(): Collection
    {
        return Tour::query()
            ->with(['location.city.country', 'categories'])
            ->where('is_active', true)
            ->get()
            ->map(function (Tour $tour): array {
                $metadata = $this->sourceMetadata('tour', $tour->getKey());
                $classification = $this->tourExploreClassification($metadata['resource_type']);

                return [
                    'id' => 'tour-'.$tour->getKey(),
                    'model_id' => $tour->getKey(),
                    'type' => $classification['type'],
                    'booking_type' => 'tour',
                    'label' => $classification['label'],
                    'name' => $tour->name,
                    'description' => $tour->description ?? $tour->short_description,
                    'latitude' => $this->nullableCoordinate($tour->location?->latitude),
                    'longitude' => $this->nullableCoordinate($tour->location?->longitude),
                    'has_coordinates' => $this->hasCoordinates($tour->location),
                    'address' => $this->address($tour->location),
                    'image' => $this->image($tour->main_image_url, 'tour'),
                    'rating' => $this->rating($tour->average_rating),
                    'base_price' => $this->money($tour->base_price),
                    'unit_price' => (float) ($tour->base_price ?? 0),
                    'phone' => null,
                    'email' => null,
                    'website' => null,
                    'featured' => (bool) $tour->is_featured,
                    'summary' => [
                        'Duration' => $tour->duration_days ? $tour->duration_days.' day(s)' : ($tour->duration_hours ? $tour->duration_hours.' hour(s)' : 'Flexible'),
                        'Capacity' => $tour->max_capacity ? 'Up to '.$tour->max_capacity : 'Flexible',
                        'From' => $this->money($tour->base_price),
                    ],
                ] + $metadata;
            });
    }

    private function transfers(): Collection
    {
        $service = Tour::query()
            ->where('is_active', true)
            ->whereHas('externalSyncMappings', fn ($query) => $query->where('source_platform', 'woo')->where('resource_type', 'tour_route'))
            ->orderBy('id')
            ->first();

        if (! $service) {
            return collect();
        }

        return collect([
            [
                'id' => 'transfer-'.$service->getKey(),
                'model_id' => $service->getKey(),
                'type' => 'transfer',
                'booking_type' => 'transfer',
                'label' => 'Transfer',
                'name' => 'Hotel & Location Transfer',
                'description' => 'Book a private Taxilanz transfer between hotels, villas and local locations.',
                'latitude' => null,
                'longitude' => null,
                'has_coordinates' => false,
                'address' => 'Choose pickup and dropoff during booking',
                'image' => $this->image($service->main_image_url, 'taxi'),
                'rating' => null,
                'base_price' => $this->money($service->base_price),
                'phone' => null,
                'email' => null,
                'website' => null,
                'featured' => true,
                'summary' => [
                    'Route' => 'Hotel · Location · Villa',
                    'Payment' => 'Redsys Nova',
                    'From' => $this->money($service->base_price),
                ],
            ] + $this->sourceMetadata('tour', $service->getKey()),
        ]);
    }

    private function sourceMetadata(string $targetModel, int $targetId): array
    {
        $mapping = ExternalSyncMapping::query()
            ->where('target_model', $targetModel)
            ->where('target_id', $targetId)
            ->latest('last_synced_at')
            ->first();

        return [
            'source_label' => $mapping?->source_label,
            'business_name' => $mapping?->business_name,
            'resource_type' => $mapping?->resource_type,
        ];
    }

    private function address($location): string
    {
        return collect([
            $location?->name,
            $location?->city?->name,
            $location?->city?->country?->name,
        ])->filter()->join(', ');
    }

    private function hasCoordinates($location): bool
    {
        return $location?->latitude !== null && $location?->longitude !== null;
    }

    private function nullableCoordinate($value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private function tourExploreClassification(?string $resourceType): array
    {
        return match ($resourceType) {
            'tour_route' => ['type' => 'taxi_route', 'label' => 'Taxi Route'],
            default => ['type' => 'tour_visit', 'label' => 'Visit Tour'],
        };
    }

    private function bookingType(string $type): string
    {
        return match ($type) {
            'tour_visit', 'taxi_route', 'tour_route', 'transfer' => 'tour',
            default => $type,
        };
    }

    /**
     * @return array<int, string>
     */
    private function defaultTourTimes(?array $sourceSettings, ?array $serverMetadata): array
    {
        $times = data_get($sourceSettings, 'availability.times')
            ?: data_get($serverMetadata, 'availability.times');

        $times = is_array($times) ? array_values(array_filter($times, fn ($value): bool => is_string($value) && $value !== '')) : null;

        return $times ?: ['10:00', '12:00', '14:00', '16:00'];
    }

    /**
     * @return array<string, int>|null keyed by "H:i" => booked participants
     */
    private function latepointRemoteBookedParticipantsByTime(?array $serverMetadata, ?string $externalServiceId, string $date): ?array
    {
        if (! is_array($serverMetadata) || blank($externalServiceId)) {
            return null;
        }

        $baseUrl = rtrim((string) data_get($serverMetadata, 'remote_endpoint'), '/');
        $path = (string) data_get($serverMetadata, 'capabilities.latepoint_bookings');
        $tokenEnv = (string) data_get($serverMetadata, 'auth_token_env');
        $localHeaderName = (string) data_get($serverMetadata, 'local_header.name');
        $localHeaderEnv = (string) data_get($serverMetadata, 'local_header.env');

        $token = $tokenEnv !== '' ? env($tokenEnv) : null;
        $localHeaderValue = $localHeaderEnv !== '' ? env($localHeaderEnv) : null;

        if (blank($baseUrl) || blank($path) || blank($token) || blank($localHeaderName) || blank($localHeaderValue)) {
            return null;
        }

        $cacheKey = 'latepoint:availability:'.sha1(json_encode([$baseUrl, $path, (string) $externalServiceId, $date]));

        return Cache::remember($cacheKey, now()->addSeconds(30), function () use ($baseUrl, $path, $token, $localHeaderName, $localHeaderValue, $externalServiceId, $date): array {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->withToken((string) $token)
                ->withHeaders([(string) $localHeaderName => (string) $localHeaderValue])
                ->post(ltrim($path, '/'), ['input' => []]);

            if (! $response->successful()) {
                return [];
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                return [];
            }

            $bookings = data_get($payload, 'data.bookings', data_get($payload, 'data', []));
            if (! is_array($bookings)) {
                return [];
            }

            $booked = [];

            foreach ($bookings as $booking) {
                if (! is_array($booking)) {
                    continue;
                }

                $serviceId = (string) ($booking['service_id'] ?? data_get($booking, 'service.id') ?? '');
                if ($serviceId === '' || (string) $externalServiceId !== $serviceId) {
                    continue;
                }

                $start = $booking['start_datetime'] ?? $booking['starts_at'] ?? null;
                if (blank($start)) {
                    continue;
                }

                $startAt = CarbonImmutable::parse($start)->format('Y-m-d H:i:s');
                if (! str_starts_with($startAt, $date.' ')) {
                    continue;
                }

                $time = CarbonImmutable::parse($startAt)->format('H:i');
                $total = (int) ($booking['participants'] ?? $booking['party_size'] ?? 1);
                $children = (int) (data_get($booking, 'custom_fields.cf_o73yLLg0') ?? data_get($booking, 'custom_fields.cf_2DaDezz1') ?? data_get($booking, 'custom_fields.cf_7Gv9c8Se') ?? data_get($booking, 'custom_fields.cf_VpfSUBKD') ?? 0);
                $adults = max(1, $total - max(0, $children));

                $booked[$time] = ($booked[$time] ?? 0) + $adults;
            }

            return $booked;
        });
    }

    private function latepointLocalBookedParticipants(string $externalServiceId, string $startsAt): int
    {
        $bookings = ExternalBooking::query()
            ->where('source_platform', 'latepoint')
            ->where('starts_at', $startsAt)
            ->where(function ($query) use ($externalServiceId) {
                $query
                    ->where('metadata->raw->service_id', $externalServiceId)
                    ->orWhere('metadata->raw->service_id', (int) $externalServiceId);
            })
            ->get(['metadata']);

        $total = 0;

        foreach ($bookings as $booking) {
            $raw = data_get($booking->metadata, 'raw', []);
            $count = (int) (data_get($raw, 'adults') ?? data_get($raw, 'participants') ?? data_get($raw, 'party_size') ?? 1);
            $total += max(1, $count);
        }

        return $total;
    }

    private function isTourSlotAvailable(Tour $tour, ?array $serverMetadata, ?string $externalServiceId, string $date, string $time, int $participants): bool
    {
        $capacity = $this->effectiveTourCapacity($tour);
        $startsAt = $date.' '.$time.':00';
        $bookedParticipants = 0;

        if (! blank($externalServiceId)) {
            $bookedParticipants += $this->latepointLocalBookedParticipants((string) $externalServiceId, $startsAt);

            $remoteBookedByTime = $this->latepointRemoteBookedParticipantsByTime($serverMetadata, (string) $externalServiceId, $date);
            if (is_array($remoteBookedByTime) && isset($remoteBookedByTime[$time])) {
                $bookedParticipants += (int) $remoteBookedByTime[$time];
            }
        }

        return ($bookedParticipants + max(1, $participants)) <= $capacity;
    }

    private function isRestaurantSlotAvailable(Restaurant $restaurant, string $date, string $time, int $guests): bool
    {
        $server = NovaMcpServer::where('type', 'sirvo')->first();

        if (! $server) {
            return true; // Si no hay servidor Sirvo, permitir reserva
        }

        $capacity = $this->sirvoClient->checkCapacity($server, $date, $time, $guests);

        if (! $capacity['checked']) {
            return true; // Si no se pudo verificar, permitir reserva
        }

        return $capacity['available'] ?? true;
    }

    private function image(?string $image, string $type): string
    {
        if ($image) {
            return str_starts_with($image, 'http') ? $image : asset('storage/'.ltrim($image, '/'));
        }

        return match ($type) {
            'hotel' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=900&q=80',
            'restaurant' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
            'taxi' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=900&q=80',
            'tour' => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=900&q=80',
        };
    }

    private function rating($rating): ?float
    {
        return $rating === null ? null : round((float) $rating, 1);
    }

    private function money($amount): string
    {
        return $amount ? '$'.number_format((float) $amount, 0) : 'On request';
    }

    private function findService(string $type, int $id): Hotel|Restaurant|TaxiService|Tour
    {
        return match ($type) {
            'hotel' => Hotel::query()->where('is_active', true)->findOrFail($id),
            'restaurant' => Restaurant::query()->where('is_active', true)->findOrFail($id),
            'taxi' => TaxiService::query()->active()->findOrFail($id),
            'tour' => Tour::query()->where('is_active', true)->findOrFail($id),
        };
    }

    private function bookingRules(string $type, ?string $originalType = null): array
    {
        $common = [
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:160'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1200'],
        ];

        return match ($type) {
            'hotel' => $common + [
                'guests' => ['required', 'integer', 'min:1', 'max:30'],
                'rooms' => ['required', 'integer', 'min:1', 'max:12'],
                'check_in_date' => ['required', 'date', 'after_or_equal:today'],
                'check_out_date' => ['required', 'date', 'after:check_in_date'],
            ],
            'restaurant' => $common + [
                'guests' => ['required', 'integer', 'min:1', 'max:30'],
                'reservation_date' => ['required', 'date', 'after_or_equal:today'],
                'reservation_time' => ['required', 'date_format:H:i'],
            ],
            'taxi' => $common + [
                'passengers' => ['required', 'integer', 'min:1', 'max:16'],
                'pickup_date_time' => ['required', 'date', 'after_or_equal:now'],
                'pickup_address' => ['required', 'string', 'max:220'],
                'dropoff_address' => ['required', 'string', 'max:220'],
            ],
            'tour' => $originalType === 'transfer' ? $common + [
                'passengers' => ['required', 'integer', 'min:1', 'max:16'],
                'pickup_date_time' => ['required', 'date', 'after_or_equal:now'],
                'pickup_address' => ['required', 'string', 'max:220'],
                'dropoff_address' => ['required', 'string', 'max:220'],
                'base_price' => ['nullable', 'numeric', 'min:0'],
            ] : $common + [
                'adults' => ['required', 'integer', 'min:1', 'max:50'],
                'children' => ['nullable', 'integer', 'min:0', 'max:50'],
                'tour_date' => ['required', 'date', 'after_or_equal:today'],
                'tour_schedule' => ['required', 'date_format:H:i'],
                'base_price' => ['nullable', 'numeric', 'min:0'],
            ] + (in_array($originalType, ['taxi_route', 'tour_route', 'transfer'], true) ? [
                'pickup_address' => ['required', 'string', 'max:220'],
                'dropoff_address' => ['required_if:'.$originalType.',transfer', 'nullable', 'string', 'max:220'],
                'passengers' => ['nullable', 'integer', 'min:1', 'max:16'],
            ] : []),
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeTransferRequest(array $validated, string $originalType): array
    {
        if (! in_array($originalType, ['taxi_route', 'tour_route', 'transfer'], true)) {
            return $validated;
        }

        $pickupDateTime = isset($validated['pickup_date_time'])
            ? CarbonImmutable::parse((string) $validated['pickup_date_time'])
            : CarbonImmutable::parse((string) $validated['tour_date'].' '.(string) $validated['tour_schedule'], 'Europe/Madrid');

        return array_merge($validated, [
            'passengers' => max(1, (int) ($validated['passengers'] ?? $validated['adults'] ?? 1)),
            'adults' => max(1, (int) ($validated['adults'] ?? $validated['passengers'] ?? 1)),
            'children' => 0,
            'tour_date' => $pickupDateTime->toDateString(),
            'tour_schedule' => $pickupDateTime->format('H:i'),
            'pickup_address' => (string) ($validated['pickup_address'] ?? ''),
            'dropoff_address' => (string) ($validated['dropoff_address'] ?? ''),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function participantsFromInputs(array $validated, bool $nullable = false): ?int
    {
        // For LatePoint visits: capacity and payment apply to adults only.
        $adults = (int) ($validated['adults'] ?? 0);
        $participants = $adults;

        if ($nullable && $participants <= 0) {
            return null;
        }

        return max(1, $participants);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function normalizeTourNotes(string $bookingType, array $validated): ?string
    {
        $notes = $validated['notes'] ?? null;

        if ($bookingType !== 'tour') {
            return $notes ?: null;
        }

        // Keep children/adults as structured fields; do not inject into free-form notes.
        return blank($notes) ? null : Str::limit(trim((string) $notes), 1200, '');
    }

    private function serviceName(object $service): string
    {
        return $service->name
            ?? $service->restaurant_name
            ?? $service->hotel_name
            ?? $service->service_name
            ?? 'Selected service';
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function findRecentMatchingBookingRequest(string $bookingType, object $service, array $validated, ?string $originalType = null): ?PublicBookingRequest
    {
        return PublicBookingRequest::query()
            ->with('assignedAdmin')
            ->where('type', $bookingType)
            ->where('service_id', $service->getKey())
            ->where('customer_email', $validated['customer_email'] ?? null)
            ->where('customer_phone', $validated['customer_phone'] ?? null)
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->subHours(24))
            ->when(in_array($originalType, ['taxi_route', 'tour_route', 'transfer'], true), function ($query) use ($originalType): void {
                $query->where('booking_kind', $originalType);
            })
            ->when($bookingType === 'tour' && ! in_array($validated['booking_kind'] ?? null, ['taxi_route', 'tour_route', 'transfer'], true), function ($query) use ($validated): void {
                $query
                    ->whereDate('tour_date', $validated['tour_date'] ?? null)
                    ->where('tour_schedule', $validated['tour_schedule'] ?? null)
                    ->where('adults', $validated['adults'] ?? null)
                    ->where('children', $validated['children'] ?? null);
            })
            ->when($bookingType === 'transfer', function ($query) use ($validated, $originalType): void {
                $query
                    ->whereDate('tour_date', $validated['tour_date'] ?? null)
                    ->where('tour_schedule', $validated['tour_schedule'] ?? null)
                    ->where('pickup_address', $validated['pickup_address'] ?? null);

                if ($originalType === 'transfer') {
                    $query->where('dropoff_address', $validated['dropoff_address'] ?? null);
                }
            })
            ->when($bookingType === 'taxi', function ($query) use ($validated): void {
                $query
                    ->where('pickup_date_time', $validated['pickup_date_time'] ?? null)
                    ->where('pickup_address', $validated['pickup_address'] ?? null)
                    ->where('dropoff_address', $validated['dropoff_address'] ?? null)
                    ->where('passengers', $validated['passengers'] ?? null);
            })
            ->latest('id')
            ->first();
    }

    private function requestReference(): string
    {
        do {
            $reference = 'REQ-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (PublicBookingRequest::where('request_reference', $reference)->exists());

        return $reference;
    }
}
