<?php

namespace App\Models\Admin;

final class DashboardAlert
{
    public function __construct(
        public readonly string $label,
        public readonly string $value,
        public readonly string $hint,
        public readonly string $tone = 'neutral',
        public readonly string $href = '/admin/',
        public readonly string $action = 'Abrir',
        public readonly string $icon = '↗',
    ) {
    }

    public static function make(string $label, string $value, string $hint, string $tone = 'neutral', string $href = '/admin/', string $action = 'Abrir', string $icon = '↗'): self
    {
        return new self($label, $value, $hint, $tone, $href, $action, $icon);
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'hint' => $this->hint,
            'tone' => $this->tone,
            'href' => $this->href,
            'action' => $this->action,
            'icon' => $this->icon,
        ];
    }
}
