<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\Enums;

enum InputType: string
{
    case TEXT = 'text';
    case AUDIO = 'audio';
    case IMAGE = 'image';
    case DOCUMENT = 'document';
    case PDF = 'pdf';
    case LOCATION = 'location';
    case CONTACT = 'contact';
    case QR = 'qr';
    case EMAIL = 'email';
    case UNKNOWN = 'unknown';
}
