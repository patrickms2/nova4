<?php

namespace App\Livewire;

use Livewire\Component;

class NovaHome extends Component
{
    public string $greeting = '';
    public array $stats = [];
    public array $feed = [];

    public function mount(): void
    {
        $this->greeting = $this->makeGreeting();

        // Demo (cámbialo luego por queries reales)
        $this->stats = [
            'active' => 7,
            'pending' => 2,
            'confirmed' => 5,
        ];

        $this->feed = [
            [
                'time' => '10:22',
                'title' => 'Taxi confirmado',
                'meta' => 'Hotel Nautilus → Aeropuerto • 2 pax',
                'tone' => 'ok',
            ],
            [
                'time' => '09:55',
                'title' => 'Reserva restaurante creada',
                'meta' => 'La Cascada • 20:30 • 4 pax',
                'tone' => 'info',
            ],
            [
                'time' => '09:12',
                'title' => 'Documento subido',
                'meta' => 'NÓMINA • Feb 2026',
                'tone' => 'muted',
            ],
        ];
    }

    public function openCreate(string $type): void
    {
        // Aquí luego abres modal, rediriges a form, etc.
        // Por ahora, disparo un evento JS para que lo veas.
        $this->dispatch('nova-toast', message: "Crear: {$type}");
    }

    private function makeGreeting(): string
    {
        $h = (int) now()->format('H');

        return match (true) {
            $h >= 6 && $h < 12 => 'Buenos días',
            $h >= 12 && $h < 20 => 'Buenas tardes',
            default => 'Buenas noches',
        };
    }

    public function render()
    {
        return view('livewire.nova-home');
    }
}