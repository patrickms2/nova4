<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('facturas.empresas')
        ->assertStatus(200);
});
