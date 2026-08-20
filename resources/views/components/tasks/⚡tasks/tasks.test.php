<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('tasks.tasks')
        ->assertStatus(200);
});
