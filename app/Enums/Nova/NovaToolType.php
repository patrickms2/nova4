<?php
declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaToolType: string
{
    case View = 'view';
    case Action = 'action';
    case Form = 'form';
    case Table = 'table';
    case Command = 'command';
    case Workflow = 'workflow';
    case Query = 'query';
    case Mutation = 'mutation';
}
