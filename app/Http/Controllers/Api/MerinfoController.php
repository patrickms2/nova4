<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merinfo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerinfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Merinfo::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('givenNameOrFirstName', 'like', "%{$search}%")
                    ->orWhere('personalNumber', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->get('per_page', 25), 100);
        $records = $query->paginate($perPage);

        return response()->json($records);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'title' => 'nullable|string',
            'short_uuid' => 'required|string|unique:merinfos,short_uuid',
            'name' => 'required|string',
            'givenNameOrFirstName' => 'required|string',
            'personalNumber' => 'required|string',
            'pnr' => 'nullable|array',
            'address' => 'nullable|array',
            'gender' => 'required|string|in:male,female,other',
            'is_celebrity' => 'boolean',
            'has_company_engagement' => 'boolean',
            'number_plus_count' => 'integer',
            'phone_number' => 'nullable|array',
            'url' => 'required|string',
            'same_address_url' => 'nullable|string',
        ]);

        $record = Merinfo::create($validated);

        return response()->json([
            'message' => 'Record created successfully',
            'data' => $record,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $record = Merinfo::findOrFail($id);

        return response()->json(['data' => $record]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $record = Merinfo::findOrFail($id);

        $validated = $request->validate([
            'type' => 'sometimes|string',
            'title' => 'nullable|string',
            'short_uuid' => 'sometimes|string|unique:merinfos,short_uuid,'.$id,
            'name' => 'sometimes|string',
            'givenNameOrFirstName' => 'sometimes|string',
            'personalNumber' => 'sometimes|string',
            'pnr' => 'nullable|array',
            'address' => 'nullable|array',
            'gender' => 'sometimes|string|in:male,female,other',
            'is_celebrity' => 'boolean',
            'has_company_engagement' => 'boolean',
            'number_plus_count' => 'integer',
            'phone_number' => 'nullable|array',
            'url' => 'sometimes|string',
            'same_address_url' => 'nullable|string',
        ]);

        $record->update($validated);

        return response()->json([
            'message' => 'Record updated successfully',
            'data' => $record,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $record = Merinfo::findOrFail($id);
        $record->delete();

        return response()->json(['message' => 'Record deleted successfully']);
    }

    /**
     * Bulk insert/update records from Merinfo API format.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'results' => 'required|array|min:1',
            'results.*.type' => 'required|string',
            'results.*.title' => 'nullable|string',
            'results.*.items' => 'required|array',
            'results.*.items.*.type' => 'required|string',
            'results.*.items.*.short_uuid' => 'required|string',
            'results.*.items.*.name' => 'required|string',
            'results.*.items.*.givenNameOrFirstName' => 'required|string',
            'results.*.items.*.personalNumber' => 'required|string',
            'results.*.items.*.pnr' => 'nullable|array',
            'results.*.items.*.address' => 'nullable|array',
            'results.*.items.*.gender' => 'required|string|in:male,female,other',
            'results.*.items.*.is_celebrity' => 'boolean',
            'results.*.items.*.has_company_engagement' => 'boolean',
            'results.*.items.*.number_plus_count' => 'integer',
            'results.*.items.*.phone_number' => 'nullable|array',
            'results.*.items.*.url' => 'required|string',
            'results.*.items.*.same_address_url' => 'nullable|string',
        ]);

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($validated['results'] as $resultIndex => $result) {
            foreach ($result['items'] as $itemIndex => $itemData) {
                try {
                    $record = Merinfo::updateOrCreate(
                        ['short_uuid' => $itemData['short_uuid']],
                        $itemData
                    );

                    $record->wasRecentlyCreated ? $created++ : $updated++;
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = [
                        'result_index' => $resultIndex,
                        'item_index' => $itemIndex,
                        'short_uuid' => $itemData['short_uuid'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return response()->json([
            'message' => 'Bulk operation completed',
            'summary' => [
                'total_processed' => count($validated['results']),
                'created' => $created,
                'updated' => $updated,
                'failed' => $failed,
            ],
            'errors' => $errors,
        ]);
    }
}
