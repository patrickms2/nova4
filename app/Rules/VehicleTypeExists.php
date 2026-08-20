<?php

namespace App\Rules;

use App\Models\VehicleType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VehicleTypeExists implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $vehicleType = VehicleType::find($value);

        if (! $vehicleType) {
            $fail('The selected vehicle type does not exist.');

            return;
        }

        if (! $vehicleType->is_active) {
            $fail('The selected vehicle type is not currently active.');
        }
    }
}
