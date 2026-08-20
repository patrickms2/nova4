<?php
declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaBindingTarget: string
{
    case Capability = 'capability';
    case Tool = 'tool';
    case Resource = 'resource';
    case Relation = 'relation';
    case Connector = 'connector';
}
