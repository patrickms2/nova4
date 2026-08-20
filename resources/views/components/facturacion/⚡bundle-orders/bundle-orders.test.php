<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('facturacion.bundle-orders')
        ->assertStatus(200);
});
