<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkOrderTaskController extends Controller
{
    public function index(WorkOrder $workOrder): JsonResponse
    {
        return response()->json(
            $workOrder->tasks()->with(['comments', 'photos'])->orderBy('sort')->get()
        );
    }

    public function store(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'instructions' => 'nullable|string',
            'requirements' => 'nullable|string',
            'priority' => 'in:low,normal,high,urgent',
            'requester_name' => 'nullable|string',
            'requester_phone' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $task = $workOrder->tasks()->create(array_merge($validated, [
            'source_type' => 'EXTRA',
            'status' => 'pending',
            'sort' => $workOrder->tasks()->max('sort') + 1,
            'created_by' => auth()->id(),
        ]));

        return response()->json($task, 201);
    }

    public function update(Request $request, WorkOrder $workOrder, WorkOrderTask $task): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string',
            'instructions' => 'nullable|string',
            'requirements' => 'nullable|string',
            'priority' => 'in:low,normal,high,urgent',
            'sort' => 'integer',
            'status' => 'in:pending,completed,not_done,cancelled',
        ]);

        $task->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

        return response()->json($task);
    }

    public function complete(Request $request, WorkOrder $workOrder, WorkOrderTask $task): JsonResponse
    {
        if ($task->status === 'completed') {
            throw ValidationException::withMessages(['status' => 'La tarea ya está completada.']);
        }

        $validated = $request->validate([
            'result' => 'required|in:correcto,con_observaciones,no_realizado,requiere_seguimiento',
            'comment' => 'nullable|string',
        ]);

        $task->update([
            'status' => 'completed',
            'result' => $validated['result'],
            'completed_by' => auth()->id(),
            'completed_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        if (! empty($validated['comment'])) {
            $task->comments()->create([
                'user_id' => auth()->id(),
                'body' => $validated['comment'],
            ]);
        }

        return response()->json($task->load('comments'));
    }
}
