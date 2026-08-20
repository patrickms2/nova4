<?php

namespace App\Services\Domotics;

use App\Models\Property;

class PinGenerator
{
    public static function generate(Property $property, int $length = 4): string
    {
        $existingPins = $property->accessGrants()->pluck('pin')->filter()->all();

        do {
            $pin = str_pad((string) random_int(10 ** ($length - 1), (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        } while (in_array($pin, $existingPins, true));

        return $pin;
    }
}
