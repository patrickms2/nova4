<?php

namespace App\Livewire\Comunigest;

use Livewire\Component;

class Settings extends Component
{
    public function render()
    {
        return view('livewire.comunigest.settings')
            ->layout('layouts.front');
    }
}
