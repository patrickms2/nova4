<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IncidentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Incident::query()->with(['community', 'resolver']);

        if ($request->filled('community_id')) {
            $query->where('community_id', $request->input('community_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->orderByDesc('id')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'community_id' => 'required|exists:communities,id',
            'work_order_id' => 'nullable|exists:work_orders,id',
            'work_order_task_id' => 'nullable|exists:work_order_tasks,id',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'priority' => 'in:low,normal,high,urgent',
        ]);

        $incident = Incident::create(array_merge($validated, [
            'status' => 'open',
            'created_by' => auth()->id(),
        ]));

        return response()->json($incident->load('community'), 201);
    }

    public function show(Incident $incident): JsonResponse
    {
        return response()->json($incident->load(['community', 'workOrder', 'workOrderTask', 'resolver', 'comments', 'photos']));
    }

    public function update(Request $request, Incident $incident): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'priority' => 'in:low,normal,high,urgent',
            'status' => 'in:open,assigned,communicated,resolved,closed',
        ]);

        $incident->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

        return response()->json($incident);
    }

    public function close(Request $request, Incident $incident): JsonResponse
    {
        if ($incident->status === 'closed') {
            throw ValidationException::withMessages(['status' => 'La incidencia ya está cerrada.']);
        }

        $validated = $request->validate([
            'resolution_note' => 'nullable|string',
        ]);

        $incident->update(array_merge($validated, [
            'status' => 'closed',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'updated_by' => auth()->id(),
        ]));

        return response()->json($incident);
    }

    public function destroy(Incident $incident): JsonResponse
    {
        $incident->delete();

        return response()->json(null, 204);
    }
}
