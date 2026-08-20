<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('tasks')
        ->assertStatus(200);
});
