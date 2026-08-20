<?php

declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaRepresentationStatus: string
{
    case Detected = 'detected';
    case Matched = 'matched';
    case Configured = 'configured';
    case Ignored = 'ignored';
}
