<?php

namespace App\Models\Admin;

final class DashboardMetric
{
    public function __construct(
        public readonly string $label,
        public readonly string $value,
        public readonly string $hint,
        public readonly string $tone = 'neutral',
        public readonly string $href = '/admin/',
        public readonly string $icon = '•',
    ) {
    }

    public static function make(string $label, string $value, string $hint, string $tone = 'neutral', string $href = '/admin/', string $icon = '•'): self
    {
        return new self($label, $value, $hint, $tone, $href, $icon);
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'hint' => $this->hint,
            'tone' => $this->tone,
            'href' => $this->href,
            'icon' => $this->icon,
        ];
    }
}
