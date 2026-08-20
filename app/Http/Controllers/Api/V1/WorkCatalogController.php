<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WorkCatalog;
use App\Models\WorkCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkCatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('with_categories')) {
            return response()->json(WorkCategory::with('catalogItems')->orderBy('sort')->get());
        }

        return response()->json(WorkCatalog::with('category')->orderBy('title')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'work_category_id' => 'required|exists:work_categories,id',
            'title' => 'required|string',
            'code' => 'nullable|string|unique:work_catalog',
            'instructions' => 'nullable|string',
            'requirements' => 'nullable|string',
            'default_priority' => 'in:low,normal,high,urgent',
        ]);

        $item = WorkCatalog::create(array_merge($validated, [
            'active' => true,
            'created_by' => auth()->id(),
        ]));

        return response()->json($item->load('category'), 201);
    }

    public function show(WorkCatalog $workCatalog): JsonResponse
    {
        return response()->json($workCatalog->load('category', 'planItems'));
    }

    public function update(Request $request, WorkCatalog $workCatalog): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string',
            'instructions' => 'nullable|string',
            'requirements' => 'nullable|string',
            'default_priority' => 'in:low,normal,high,urgent',
            'active' => 'boolean',
        ]);

        $workCatalog->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

        return response()->json($workCatalog);
    }

    public function destroy(WorkCatalog $workCatalog): JsonResponse
    {
        $workCatalog->delete();

        return response()->json(null, 204);
    }
}
