<?php

namespace App\Actions\Community;

use App\Models\WorkOrder;
use InvalidArgumentException;

class TransitionWorkOrder
{
    public function handle(WorkOrder $order, string $status, ?int $actorId): void
    {
        if (! in_array($status, ['pending', 'in_progress', 'finished', 'cancelled'], true)) {
            throw new InvalidArgumentException('Unsupported work order status.');
        }

        $changes = ['status' => $status, 'updated_by' => $actorId];
        if ($status === 'in_progress') {
            $changes += ['started_by' => $actorId, 'started_at' => now()];
        }
        if ($status === 'finished') {
            $changes += ['finished_by' => $actorId, 'finished_at' => now()];
        }
        if ($status === 'pending') {
            $changes += ['started_by' => null, 'started_at' => null, 'finished_by' => null, 'finished_at' => null];
        }
        $order->update($changes);
    }
}
