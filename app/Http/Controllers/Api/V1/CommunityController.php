<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Community;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Community::query()->with('creator');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        return response()->json($query->orderBy('name')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:communities',
            'name' => 'required|string',
            'address' => 'nullable|string',
            'contact_name' => 'nullable|string',
            'contact_phone' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $community = Community::create(array_merge($validated, [
            'status' => $request->input('status', 'active'),
            'created_by' => auth()->id(),
        ]));

        return response()->json($community, 201);
    }

    public function show(Community $community): JsonResponse
    {
        return response()->json($community->load(['plans.items.days', 'workOrders']));
    }

    public function update(Request $request, Community $community): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'address' => 'nullable|string',
            'contact_name' => 'nullable|string',
            'contact_phone' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'sometimes|required|string',
        ]);

        $community->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

        return response()->json($community);
    }

    public function destroy(Community $community): JsonResponse
    {
        $community->delete();

        return response()->json(null, 204);
    }
}
