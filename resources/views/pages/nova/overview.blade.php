<?php

use function Livewire\Volt\{layout, state, title};

layout('layouts.nova');
title('Nova');

state([
    'workspace' => 'Default Workspace',
    'status' => 'READY',
]);
?>

<div class="nova-card">
    <img src="{{ asset('nova/logo.svg') }}" width="48" height="48" alt="Nova">
    <h1>{{ $workspace }}</h1>
    <p class="nova-status">{{ $status }}</p>
</div>
