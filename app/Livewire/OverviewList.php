<?php

namespace App\Livewire\Overview;

use App\Models\Category;
use Livewire\Component;

class OverviewList extends Component
{
    public array $categoryFilter = [];
    public $categories;
    public $products;
    public $productId;

    public function mount()
    {
        $this->loadCategory();
    }

    private function loadCategory(): void
    {
        $currentProductId = app(\App\Services\UserProductFilterService::class)->getSelectedProductId();
        $currentCompany = company(); // Assuming this is a global helper

        $this->productId = $currentProductId ?? $this->productId;

        if (!$this->productId || !$currentCompany) {
            redirect()->route('product.index'); // Redirect if missing product or company
            return;
        }

        $defaultSlugs = array_keys(__('modules.categories.default_categories'));

        $this->categories = Category::where('product_id', $this->productId)
            ->whereNot('name', __('modules.categories.default_categories.default'))
            ->where('company_id', $currentCompany->id)
            ->orderByRaw("FIELD(slug, '" . implode("','", $defaultSlugs) . "') DESC")
            ->orderBy('id', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.overview.overview-list', [
            'categories' => $this->categories,
        ]);
    }
}
