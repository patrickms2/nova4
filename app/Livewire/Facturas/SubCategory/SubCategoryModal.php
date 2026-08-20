<?php

namespace App\Livewire\Facturas\SubCategory;

use App\Models\Category;
use App\Models\SubCategory;
use Developermithu\Tallcraftui\Traits\WithTcToast;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class SubCategoryModal extends Component
{
    use WithTcToast;

    public $subCategoryId = null;
    public $isView = false;

    public string $icon = '';
    public ?int $category_id = null;

    public $name = null, $status = 'active';

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255|unique:sub_categories,name,' . $this->subCategoryId,
            'icon'             => 'nullable|string|max:64',
            'status' => 'required|in:active,disable',
        ];
    }

    public function updated($propertyName)
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->validateOnly($propertyName);
    }

    // Submit the form data
    public function submit()
    {

        // dd($this->iconStyle);
        $this->validate();

        // Prepare the payload
        $saveData = [
            'user_id'    => Auth::user()->id,
            'category_id'    => $this->category_id,
            'name'       => $this->name,
            'icon'       => $this->icon,
            'status'     => $this->status,
        ];

        if ($this->subCategoryId) {
            $subCategory = SubCategory::find($this->subCategoryId);

            if (!$subCategory) {
                $this->error(
                    title: 'Sub-Category not found!',
                    position: 'top-right',
                    showProgress: true,
                    showCloseIcon: true,
                );
                return;
            }

            // Check for changes WITHOUT persisting
            $original = $subCategory->getAttributes();
            $subCategory->fill($saveData);
            $hasChanges = $subCategory->isDirty();
            $subCategory->fill($original); // revert to original before actual update

            if (!$hasChanges) {
                $this->warning(
                    title: 'Nothing to update!',
                    position: 'top-right',
                    showProgress: true,
                    showCloseIcon: true,
                );
                $this->dispatch('subCategories:refresh');
                Flux::modal('subCategory-modal')->close();
                return;
            }

            // Persist updates
            $subCategory->update($saveData);

            $this->success(
                title: 'Sub-Category updated successfully.',
                position: 'top-right',
                showProgress: true,
                showCloseIcon: true,
            );
        } else {
            SubCategory::create($saveData);

            // Reset fields you want cleared for a fresh form (adjust as needed)
            $this->reset();
            $this->status = 'active';

            $this->success(
                title: 'Sub-Category created successfully.',
                position: 'top-right',
                showProgress: true,
                showCloseIcon: true,
            );
        }

        $this->dispatch('subCategories:refresh');
        Flux::modal('subCategory-modal')->close();
    }


    #[On('open-subCategory-modal')]
    public function subCategoryDetail($mode, $subCategory = null)
    {

        $this->resetErrorBag();
        $this->resetValidation();

        $this->isView = $mode === 'view';

        if ($mode === 'create') {

            $this->isView = false;
            $this->reset();
            $this->status = 'active';
        } else {
            // dd($subCategory);
            $this->subCategoryId = $subCategory['id'] ?? null;
            $this->name = $subCategory['name'] ?? null;
            $this->icon = $subCategory['icon'] ?? '';
            $this->category_id   = $subCategory['category_id'] ?? null;
            $this->status = $subCategory['status'] ?? 'active';
        }
    }
    
    public function render()
    {
        $categories = Category::where('user_id', Auth::user()->id)
            ->where('status', 'active')
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);
            
        return view('livewire.sub-category.sub-category-modal', [
            'categories' => $categories,
        ]);
    }
}
