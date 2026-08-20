<?php

namespace App\Actions\Community;

use App\Models\CommunityPlan;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class RegenerateCommunityPlanWorkOrders
{
    public function handle(CommunityPlan $plan, ?int $actorId, array $assignments = []): void
    {
        $plan->loadMissing('items.days', 'items.catalog', 'items.candidateEmployees');
        $reference = 'PLAN-'.$plan->id.'-'.$plan->name;

        foreach ($assignments as $planItemId => $employeeId) {
            $item = $plan->items->firstWhere('id', (int) $planItemId);

            if (! $item || ! $item->candidateEmployees->contains('id', (int) $employeeId)) {
                throw ValidationException::withMessages(["assignments.{$planItemId}" => 'El empleado seleccionado no es candidato para este servicio.']);
            }
        }

        WorkOrder::where('community_id', $plan->community_id)->where('community_plan_id', $plan->id)->delete();

        if ($plan->status !== 'active') {
            return;
        }

        $current = Carbon::parse($plan->valid_from);
        $until = $plan->valid_until ? Carbon::parse($plan->valid_until) : $current->copy();

        while ($current->lte($until)) {
            $items = $plan->items->filter(fn ($item): bool => $item->active && $item->days->pluck('day_of_week')->map(fn ($day): int => (int) $day)->contains($current->isoWeekday()));

            if ($items->isNotEmpty()) {
                $count = WorkOrder::whereDate('work_date', $current->toDateString())->count() + 1;
                $order = WorkOrder::create([
                    'community_id' => $plan->community_id,
                    'community_plan_id' => $plan->id,
                    'code' => 'OT-'.$current->format('Ymd').'-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT).'-'.$plan->name,
                    'work_date' => $current->toDateString(),
                    'status' => 'pending',
                    'reference' => $reference,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                foreach ($items->values() as $index => $item) {
                    $order->tasks()->create([
                        'community_plan_item_id' => $item->id,
                        'community_plan_id' => $plan->id,
                        'community_id' => $plan->community_id,
                        'community_order_id' => $order->id,
                        'user_id' => $assignments[$item->id] ?? null,
                        'source_type' => 'PLAN',
                        'title' => $item->title ?? $plan->community->name.' - '.$plan->name.' - '.$current->format('d/m/Y'),
                        'instructions' => $item->instructions,
                        'requirements' => $item->requirements,
                        'priority' => $item->catalog?->default_priority ?? 'normal',
                        'status' => 'pending',
                        'sort' => $index,
                        'created_by' => $actorId,
                        'updated_by' => $actorId,
                    ]);
                }
            }

            $current->addDay();
        }
    }
}
