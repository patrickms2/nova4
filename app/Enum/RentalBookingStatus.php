<?php

namespace App\Enum;

enum RentalBookingStatus: string
{
    case RESERVED = 'reserved';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
