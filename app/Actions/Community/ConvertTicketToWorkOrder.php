<?php

namespace App\Actions\Community;

use App\Models\CommunityTicket;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class ConvertTicketToWorkOrder
{
    public function handle(CommunityTicket $ticket, ?int $actorId): WorkOrder
    {
        return DB::transaction(function () use ($ticket, $actorId): WorkOrder {
            $ticket = CommunityTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            $reference = 'COMMUNITY-TICKET-'.$ticket->id;

            $order = WorkOrder::withTrashed()->firstOrCreate(
                ['reference' => $reference],
                [
                    'community_id' => $ticket->community_id,
                    'code' => 'OT-TICKET-'.$ticket->id,
                    'work_date' => $ticket->due_at?->toDateString() ?? today()->toDateString(),
                    'status' => 'pending',
                    'source_type' => 'community_ticket',
                    'requester_name' => $ticket->person?->display_name,
                    'requester_phone' => $ticket->person?->phone,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ],
            );

            if ($order->trashed()) {
                $order->restore();
            }

            $order->tasks()->firstOrCreate(
                ['reference' => $reference],
                [
                    'source_type' => 'COMMUNITY_TICKET',
                    'title' => $ticket->title,
                    'instructions' => $ticket->description,
                    'priority' => $ticket->priority,
                    'status' => 'pending',
                    'requester_name' => $ticket->person?->display_name,
                    'requester_phone' => $ticket->person?->phone,
                    'sort' => 0,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ],
            );

            if (! in_array($ticket->status, ['resolved', 'closed'], true)) {
                $ticket->update(['status' => 'in_progress']);
            }

            return $order->load('tasks');
        });
    }
}
