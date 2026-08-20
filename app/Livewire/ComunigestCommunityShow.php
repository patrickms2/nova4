<?php

namespace App\Livewire;

use App\Actions\Community\RegenerateCommunityPlanWorkOrders;
use App\Models\Community;
use App\Models\CommunityPlan;
use App\Models\CommunityPlanItem;
use App\Models\WorkCatalog;
use App\Models\WorkCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ComunigestCommunityShow extends Component
{
    public Community $community;

    public bool $showPlanModal = false;

    public ?int $planId = null;

    public int $planStep = 1;

    public string $planValidFrom = '';

    public string $planValidUntil = '';

    public string $planStatus = 'draft';

    /** @var array<int, array<string, mixed>> */
    public array $planItems = [];

    public function mount(Community $community): void
    {
        $this->community = $community->load([
            'plans.items.days',
            'workOrders' => function ($query) {
                $query->latest()->with('tasks');
            },
        ]);
    }

    public function openPlanModal(?int $id = null): void
    {
        if ($id) {
            $plan = CommunityPlan::with('items.catalog', 'items.days')->findOrFail($id);

            $this->planId = $plan->id;
            $this->planValidFrom = $plan->valid_from?->format('Y-m-d');
            $this->planValidUntil = $plan->valid_until?->format('Y-m-d') ?? '';
            $this->planStatus = $plan->status;
            $this->planStep = 1;
            $this->planItems = $plan->items->map(fn (CommunityPlanItem $item) => [
                'id' => $item->id,
                'categoryId' => $item->work_catalog_id ? ($item->catalog?->work_category_id ?? null) : null,
                'catalogId' => $item->work_catalog_id,
                'title' => $item->title,
                'instructions' => $item->instructions ?? '',
                'requirements' => $item->requirements ?? '',
                'days' => $item->days->pluck('day_of_week')->toArray(),
            ])->toArray();
        } else {
            $this->planId = null;
            $this->planStep = 1;
            $this->planValidFrom = now()->format('Y-m-d');
            $this->planValidUntil = '';
            $this->planStatus = 'active';
            $this->planItems = [['id' => null, 'categoryId' => null, 'catalogId' => null, 'title' => '', 'instructions' => '', 'requirements' => '', 'days' => []]];
        }

        $this->showPlanModal = true;
    }

    public function closePlanModal(): void
    {
        $this->showPlanModal = false;
        $this->planId = null;
        $this->planStep = 1;
        $this->planValidFrom = '';
        $this->planValidUntil = '';
        $this->planStatus = 'draft';
        $this->planItems = [];
    }

    public function addPlanItem(): void
    {
        $this->planItems[] = ['id' => null, 'categoryId' => null, 'catalogId' => null, 'title' => '', 'instructions' => '', 'requirements' => '', 'days' => []];
    }

    public function removePlanItem(int $index): void
    {
        unset($this->planItems[$index]);
        $this->planItems = array_values($this->planItems);
    }

    public function selectCatalog(int $index): void
    {
        $catalogId = $this->planItems[$index]['catalogId'] ?? null;

        if ($catalogId) {
            $catalog = WorkCatalog::with('category')->find($catalogId);
            if ($catalog) {
                $this->planItems[$index]['categoryId'] = $catalog->work_category_id;
                $this->planItems[$index]['title'] = $catalog->title;
                $this->planItems[$index]['instructions'] = $catalog->instructions ?? '';
                $this->planItems[$index]['requirements'] = $catalog->requirements ?? '';
            }
        }
    }

    public function nextStep(): void
    {
        if ($this->planStep === 1) {
            $this->validate([
                'planValidFrom' => 'required|date',
                'planValidUntil' => 'nullable|date|after_or_equal:planValidFrom',
                'planStatus' => 'required|in:draft,active',
            ]);
        }

        if ($this->planStep === 2) {
            $this->validate([
                'planItems' => 'required|array|min:1',
                'planItems.*.categoryId' => 'required|exists:work_categories,id',
                'planItems.*.catalogId' => 'required|exists:work_catalog,id',
                'planItems.*.title' => 'required|string|max:255',
                'planItems.*.instructions' => 'nullable|string',
                'planItems.*.requirements' => 'nullable|string',
            ]);
        }

        $this->planStep = min(3, $this->planStep + 1);
    }

    public function prevStep(): void
    {
        $this->planStep = max(1, $this->planStep - 1);
    }

    public function savePlan(): void
    {
        $this->validate([
            'planValidFrom' => 'required|date',
            'planValidUntil' => 'nullable|date|after_or_equal:planValidFrom',
            'planStatus' => 'required|in:draft,active',
            'planItems' => 'required|array|min:1',
            'planItems.*.categoryId' => 'required|exists:work_categories,id',
            'planItems.*.catalogId' => 'required|exists:work_catalog,id',
            'planItems.*.title' => 'required|string|max:255',
            'planItems.*.instructions' => 'nullable|string',
            'planItems.*.requirements' => 'nullable|string',
            'planItems.*.days' => 'nullable|array',
            'planItems.*.days.*' => 'in:1,2,3,4,5,6,7',
        ]);

        $plan = $this->planId
            ? CommunityPlan::findOrFail($this->planId)
            : new CommunityPlan(['community_id' => $this->community->id]);

        $plan->valid_from = $this->planValidFrom;
        $plan->valid_until = $this->planValidUntil ?: null;
        $plan->status = $this->planStatus;
        $plan->updated_by = Auth::id();

        if (! $this->planId) {
            $plan->created_by = Auth::id();
        }

        $plan->save();

        if ($this->planId) {
            $plan->items()->delete();
        }

        foreach ($this->planItems as $index => $item) {
            $planItem = $plan->items()->create([
                'work_catalog_id' => $item['catalogId'],
                'title' => $item['title'],
                'instructions' => $item['instructions'] ?: null,
                'requirements' => $item['requirements'] ?: null,
                'sort' => $index,
                'active' => true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($item['days'] ?? [] as $day) {
                $planItem->days()->create(['day_of_week' => (int) $day]);
            }
        }

        app(RegenerateCommunityPlanWorkOrders::class)->handle($plan->fresh('items.days', 'items.catalog'), Auth::id());

        $this->community->load(['plans.items.days']);
        $this->closePlanModal();
    }

    public function deletePlan(int $id): void
    {
        CommunityPlan::findOrFail($id)->delete();
        $this->community->load(['plans.items.days']);
    }

    public function render()
    {
        $workCategories = WorkCategory::where('active', true)
            ->orderBy('sort')
            ->with(['catalogItems' => fn ($q) => $q->where('active', true)->orderBy('title')])
            ->get();

        return view('livewire.comunigest-community-show', [
            'workCategories' => $workCategories,
        ])->layout('layouts.front');
    }
}
