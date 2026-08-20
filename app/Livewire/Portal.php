<?php

namespace App\Livewire;

use Livewire\Component;

class Portal extends Component
{
    public function render()
    {
        return view('livewire.portal')
            ->layout('components.layouts.guest', ['title' => __('Portal Taxista')]);
    }
}
