<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\Enums;

enum IntentName: string
{
    case GREETING = 'greeting';
    case MENU = 'menu';
    case OPERATIONAL_MENU = 'operational_menu';
    case CONSULT = 'consult';
    case CREATE = 'create';
    case EDIT = 'edit';
    case DELETE = 'delete';
    case IMPORT = 'import';
    case SEND = 'send';
    case ANALYZE = 'analyze';
    case COMPARE = 'compare';
    case CONFIGURE = 'configure';
    case REPLY = 'reply';
    case CONFIRM = 'confirm';
    case CANCEL = 'cancel';
    case UNKNOWN = 'unknown';
}
