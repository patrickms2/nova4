<?php

declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaFormMode: string
{
    case Page = 'page';
    case Modal = 'modal';
    case SlideOver = 'slide_over';
}
