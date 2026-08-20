<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\NovaRequest;

class NovaCta extends Component
{
    public string $context = 'TRABAJO';
    public bool $expanded = false;

    public function mount(): void
    {
        $this->context = session('nova_context', 'TRABAJO');
    }

    public function toggle(): void
    {
        $this->expanded = !$this->expanded;
    }

    public function collapse(): void
    {
        $this->expanded = false;
    }

    /**
     * Acción Livewire REAL (ejemplo): crear request rápida.
     */
    public function quickCreate(string $type = 'support_ticket'): void
    {
        $title = match ($type) {
            'document_delivery' => 'Document delivery · ' . now()->format('H:i'),
            'table_booking'     => 'Table booking · ' . now()->format('H:i'),
            default             => 'Support ticket · ' . now()->format('H:i'),
        };

        NovaRequest::create([
            'type' => $type,
            'status' => 'pending',
            'title' => $title,
            'summary' => 'Creada desde el CTA.',
            'context' => [
                'priority' => 'normal',
                'source'   => strtolower($this->context),
            ],
            'user_id' => auth()->id(),
        ]);

        // Cierra el panel
        $this->expanded = false;

        // Feedback (si tienes un sistema de toasts, aquí lo engancharías)
        $this->dispatch('nova-toast', type: 'success', message: 'Solicitud creada');
    }

    public function openSpotlight(): void
    {
        $this->dispatch('open-command-palette');
        $this->expanded = false;
    }

    public function getActionsProperty(): array
    {
        // Acciones por contexto (mezcla links + livewire + spotlight)
        return match ($this->context) {
            'TRABAJO' => [
                ['type' => 'livewire', 'label' => 'Nueva solicitud', 'icon' => 'plus', 'action' => 'quickCreate', 'payload' => 'support_ticket'],
                ['type' => 'event',    'label' => 'Spotlight',       'icon' => 'search','event'  => 'open-command-palette'],
                ['type' => 'link',     'label' => 'Login Taxista',  'icon' => 'identification','href'   => route('taxista.login'), 'target' => '_self'],
                ['type' => 'link',     'label' => 'Portal Pro',      'icon' => 'sparkles','href'   => route('portal-pro'), 'target' => '_self'],
                ['type' => 'link',     'label' => 'Portal Móvil',   'icon' => 'device-phone-mobile','href'   => route('mobile-portal'), 'target' => '_self'],
                ['type' => 'link',     'label' => 'Panel Staff',    'icon' => 'shield','href'   => route('app'),    'target' => '_self'],
                ['type' => 'link',     'label' => 'Portal Taxi',    'icon' => 'car','href'   => route('portal'), 'target' => '_self'],
                ['type' => 'link',     'label' => 'App Filament',   'icon' => 'switch','href'   => 'https://taxilanzhr.test/app',    'target' => '_blank'],
                ['type' => 'link',     'label' => 'Portal Filament','icon' => 'switch','href'   => 'https://taxilanzhr.test/portal', 'target' => '_blank'],
                ['type' => 'link',     'label' => 'Solidtime',      'icon' => 'clock', 'href'   => 'https://solidtime.test',         'target' => '_blank'],
                ['type' => 'link',     'label' => 'Agenda',         'icon' => 'calendar','href' => 'https://calendar.google.com',     'target' => '_blank'],
                ['type' => 'link',     'label' => 'ClickUp',        'icon' => 'link',  'href'   => 'https://app.clickup.com',        'target' => '_blank'],
            ],
            'OCIO' => [
                ['type' => 'event',    'label' => 'Spotlight',   'icon' => 'search','event' => 'open-command-palette'],
                ['type' => 'livewire', 'label' => 'Nueva idea',  'icon' => 'spark', 'action' => 'quickCreate', 'payload' => 'idea'],
                ['type' => 'link',     'label' => 'Restaurantes','icon' => 'link',  'href' => 'https://canaryclick.test/restaurantes', 'target' => '_blank'],
            ],
            default => [
                ['type' => 'event',    'label' => 'Spotlight',   'icon' => 'search','event' => 'open-command-palette'],
                ['type' => 'livewire', 'label' => 'Nuevo task',  'icon' => 'plus',  'action' => 'quickCreate', 'payload' => 'task'],
            ],
        };
    }

    public function render()
    {
        return view('livewire.nova-cta');
    }
}