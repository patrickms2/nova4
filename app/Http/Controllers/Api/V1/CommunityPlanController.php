<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityPlanController extends Controller
{
    public function index(Community $community): JsonResponse
    {
        return response()->json(
            $community->plans()->with('items.days')->orderByDesc('id')->get()
        );
    }

    public function store(Request $request, Community $community): JsonResponse
    {
        $validated = $request->validate([
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'status' => 'in:draft,active',
        ]);

        $plan = $community->plans()->create(array_merge($validated, [
            'status' => $request->input('status', 'draft'),
            'created_by' => auth()->id(),
        ]));

        return response()->json($plan->load('items.days'), 201);
    }

    public function show(CommunityPlan $communityPlan): JsonResponse
    {
        return response()->json($communityPlan->load('community', 'items.days'));
    }

    public function update(Request $request, CommunityPlan $communityPlan): JsonResponse
    {
        $validated = $request->validate([
            'valid_from' => 'sometimes|required|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'status' => 'in:draft,active,inactive,replaced',
        ]);

        $communityPlan->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

        return response()->json($communityPlan);
    }

    public function destroy(CommunityPlan $communityPlan): JsonResponse
    {
        $communityPlan->delete();

        return response()->json(null, 204);
    }
}
