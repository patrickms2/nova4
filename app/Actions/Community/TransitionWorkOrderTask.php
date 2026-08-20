<?php

namespace App\Actions\Community;

use App\Models\WorkOrderTask;
use InvalidArgumentException;

class TransitionWorkOrderTask
{
    public function handle(WorkOrderTask $task, string $status, ?int $actorId, ?string $result = null): void
    {
        if (! in_array($status, ['pending', 'resolved', 'in_progress', 'cancelled'], true)) {
            throw new InvalidArgumentException('Unsupported task status.');
        }
        $task->update([
            'status' => $status,
            'completed_by' => $status === 'resolved' ? $actorId : null,
            'completed_at' => $status === 'resolved' ? now() : null,
            'result' => $status === 'resolved' ? ($result ?? 'correcto') : null,
            'updated_by' => $actorId,
        ]);
    }
}
