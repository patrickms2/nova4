<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\WorkOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkOrderController extends Controller
{
    public function index(Request $request, ?Community $community = null): JsonResponse
    {
        $query = WorkOrder::query()->with(['community', 'starter', 'finisher']);

        if ($community) {
            $query->where('community_id', $community->id);
        }

        if ($request->filled('date')) {
            $query->where('work_date', $request->input('date'));
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
            'work_date' => 'required|date',
            'requester_name' => 'nullable|string',
            'requester_phone' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $code = 'OT-'.now()->format('Ymd').'-'.str_pad((WorkOrder::where('work_date', $validated['work_date'])->count() + 1), 3, '0', STR_PAD_LEFT);

        $order = WorkOrder::create(array_merge($validated, [
            'code' => $code,
            'created_by' => auth()->id(),
        ]));

        return response()->json($order, 201);
    }

    public function show(WorkOrder $workOrder): JsonResponse
    {
        return response()->json($workOrder->load(['community', 'starter', 'finisher', 'tasks.comments', 'tasks.photos', 'comments', 'incidents']));
    }

    public function update(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $validated = $request->validate([
            'requester_name' => 'nullable|string',
            'requester_phone' => 'nullable|string',
            'reference' => 'nullable|string',
            'status' => 'in:pending,in_progress,finished,cancelled',
        ]);

        $workOrder->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

        return response()->json($workOrder);
    }

    public function destroy(WorkOrder $workOrder): JsonResponse
    {
        $workOrder->delete();

        return response()->json(null, 204);
    }

    public function start(Request $request, WorkOrder $workOrder): JsonResponse
    {
        if (in_array($workOrder->status, ['finished', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'La orden ya está cerrada o cancelada.']);
        }

        $workOrder->update([
            'status' => 'in_progress',
            'started_by' => auth()->id(),
            'started_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json($workOrder->load('tasks'));
    }

    public function finish(Request $request, WorkOrder $workOrder): JsonResponse
    {
        if (in_array($workOrder->status, ['finished', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'La orden ya está cerrada o cancelada.']);
        }

        $workOrder->update([
            'status' => 'finished',
            'finished_by' => auth()->id(),
            'finished_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json($workOrder->load('tasks'));
    }
}
