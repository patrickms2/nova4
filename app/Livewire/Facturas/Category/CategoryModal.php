<?php

namespace App\Livewire\Facturas\Category;

use App\Models\Category;
use Developermithu\Tallcraftui\Traits\WithTcToast;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class CategoryModal extends Component
{
    use WithFileUploads;
    use WithTcToast;

    public $categoryId = null;
    public $isView = false;

    public string $icon = '';

    public $name = null, $status = 'active';

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:categories,name,' . $this->categoryId,
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
            'name'       => $this->name,
            'icon'       => $this->icon,
            'status'     => $this->status,
        ];

        if ($this->categoryId) {
            $category = Category::find($this->categoryId);

            if (!$category) {
                $this->error(
                    title: 'Category not found!',
                    position: 'top-right',
                    showProgress: true,
                    showCloseIcon: true,
                );
                return;
            }

            // Check for changes WITHOUT persisting
            $original = $category->getAttributes();
            $category->fill($saveData);
            $hasChanges = $category->isDirty();
            $category->fill($original); // revert to original before actual update

            if (!$hasChanges) {
                $this->warning(
                    title: 'Nothing to update!',
                    position: 'top-right',
                    showProgress: true,
                    showCloseIcon: true,
                );
                $this->dispatch('categories:refresh');
                Flux::modal('category-modal')->close();
                return;
            }

            // Persist updates
            $category->update($saveData);

            $this->success(
                title: 'Category updated successfully.',
                position: 'top-right',
                showProgress: true,
                showCloseIcon: true,
            );
        } else {
            Category::create($saveData);

            // Reset fields you want cleared for a fresh form (adjust as needed)
            $this->reset();
            $this->status = 'active';

            $this->success(
                title: 'Category created successfully.',
                position: 'top-right',
                showProgress: true,
                showCloseIcon: true,
            );
        }

        $this->dispatch('categories:refresh');
        Flux::modal('category-modal')->close();
    }


    #[On('open-category-modal')]
    public function categoryDetail($mode, $category = null)
    {

        $this->resetErrorBag();
        $this->resetValidation();

        $this->isView = $mode === 'view';

        if ($mode === 'create') {

            $this->isView = false;
            $this->reset();
            $this->status = 'active';
        } else {
            // dd($category);
            $this->categoryId = $category['id'];

            $this->name = $category['name'];
            $this->icon = $category['icon'];
            $this->status = $category['status'];
        }
    }

    public function render()
    {

        return view('livewire.category.category-modal');
    }
}
