<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('notes.notes')
        ->assertStatus(200);
});
