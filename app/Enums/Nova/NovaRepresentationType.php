<?php
declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaRepresentationType: string
{
    case Livewire = 'livewire';
    case Filament = 'filament';
    case Native = 'native';
    case Web = 'web';
    case Api = 'api';
    case Mcp = 'mcp';
    case WhatsApp = 'whatsapp';
}
