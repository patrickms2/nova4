<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('community-portal')
        ->assertStatus(200);
});
