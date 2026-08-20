<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Panel;
use Livewire\Attributes\Validate;
use Illuminate\Support\Str;

class PanelCreateForm extends Component
{
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|max:255|unique:panels,slug')]
    public $slug = '';

    #[Validate('nullable|string|max:65535')]
    public $description = '';

    #[Validate('nullable|string|max:255')]
    public $navigation_group = '';

    #[Validate('nullable|integer|min:0')]
    public $navigation_sort = 0;

    #[Validate('nullable|string|max:255')]
    public $icon = 'heroicon-o-cube';

    #[Validate('boolean')]
    public $is_active = true;

    public function updatedName($value)
    {
        $this->slug = Str::slug($value);
    }

    public function save()
    {
        $this->validate();

        $panel = Panel::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'navigation_group' => $this->navigation_group,
            'navigation_sort' => $this->navigation_sort,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
            'model_schema' => [
                'model_name' => str_replace(' ', '', $this->name),
                'table_name' => strtolower(str_replace(' ', '_', $this->name)),
                'fields' => [],
                'relations' => [],
            ],
        ]);

        $this->dispatch('panel-created', panelId: $panel->id);

        $this->reset();

        session()->flash('message', 'Panel created successfully!');
    }

    public function render()
    {
        return view('livewire.panel-create-form');
    }
}
