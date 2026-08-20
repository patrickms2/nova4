<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\TaxiService\TaxiServiceManagementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class TaxiServiceController extends Controller
{
    /**
     * The taxi service service instance.
     *
     * @var TaxiServiceManagementService
     */
    protected $taxiServiceService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TaxiServiceManagementService $taxiServiceService)
    {
        $this->taxiServiceService = $taxiServiceService;
    }

    /**
     * Display a listing of the taxi services.
     *
     * @return Response
     */
    public function index()
    {
        try {
            $taxiServices = $this->taxiServiceService->getAllTaxiServices();
            if ($taxiServices->isEmpty()) {
                return response()->json(['message' => 'No taxi services found'], 404);
            }

            return response()->json(['data' => $taxiServices]);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['Failed to retrieve taxi services: '.$e->getMessage()]], 500);
        }
    }

    /**
     * Store a newly created taxi service in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ServiceName' => 'required|string|max:255',
            'Description' => 'nullable|string',
            'LocationID' => 'required|exists:Locations,LocationID',
            'LogoURL' => 'nullable|string',
            'Website' => 'nullable|string|url',
            'Phone' => 'nullable|string',
            'Email' => 'nullable|email',
            'IsActive' => 'boolean',
            'ManagerID' => 'nullable|exists:users,UserID',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $taxiService = $this->taxiServiceService->createTaxiService($request->all());

            return response()->json(['data' => $taxiService, 'message' => 'Taxi service created successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['Failed to create taxi service: '.$e->getMessage()]], 500);
        }
    }

    /**
     * Display the specified taxi service.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        try {
            $taxiService = $this->taxiServiceService->getTaxiServiceById($id);

            return response()->json(['data' => $taxiService]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['errors' => ['Taxi service not found']], 404);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['Failed to retrieve taxi service: '.$e->getMessage()]], 500);
        }
    }

    /**
     * Update the specified taxi service in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'ServiceName' => 'string|max:255',
            'Description' => 'nullable|string',
            'LocationID' => 'exists:Locations,LocationID',
            'LogoURL' => 'nullable|string',
            'Website' => 'nullable|string|url',
            'Phone' => 'nullable|string',
            'Email' => 'nullable|email',
            'IsActive' => 'boolean',
            'ManagerID' => 'nullable|exists:users,UserID',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $taxiService = $this->taxiServiceService->updateTaxiService($id, $request->all());

            return response()->json(['data' => $taxiService, 'message' => 'Taxi service updated successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['errors' => ['Taxi service not found']], 404);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['Failed to update taxi service: '.$e->getMessage()]], 500);
        }
    }

    /**
     * Remove the specified taxi service from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        try {
            $result = $this->taxiServiceService->deleteTaxiService($id);
            if ($result) {
                return response()->json(['message' => 'Taxi service deleted successfully']);
            }

            return response()->json(['errors' => ['Failed to delete taxi service']], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json(['errors' => ['Taxi service not found']], 404);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['Failed to delete taxi service: '.$e->getMessage()]], 500);
        }
    }

    /**
     * Get all active taxi services.
     *
     * @return Response
     */
    public function getActiveTaxiServices()
    {
        try {
            $taxiServices = $this->taxiServiceService->getActiveTaxiServices();
            if ($taxiServices->isEmpty()) {
                return response()->json(['message' => 'No active taxi services found'], 404);
            }

            return response()->json(['data' => $taxiServices]);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['Failed to retrieve active taxi services: '.$e->getMessage()]], 500);
        }
    }

    /**
     * Get taxi services by location.
     *
     * @param  int  $locationId
     * @return Response
     */
    public function getTaxiServicesByLocation($locationId)
    {
        try {
            $taxiServices = $this->taxiServiceService->getTaxiServicesByLocation($locationId);
            if ($taxiServices->isEmpty()) {
                return response()->json(['message' => 'No taxi services found for this location'], 404);
            }

            return response()->json(['data' => $taxiServices]);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['Failed to retrieve taxi services by location: '.$e->getMessage()]], 500);
        }
    }
}
