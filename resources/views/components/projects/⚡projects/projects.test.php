<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('projects.projects')
        ->assertStatus(200);
});
