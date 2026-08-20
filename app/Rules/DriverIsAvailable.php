<?php

namespace App\Rules;

use App\Models\Driver;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DriverIsAvailable implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $driver = Driver::find($value);

        if (! $driver) {
            $fail('The driver does not exist.');

            return;
        }

        if (! $driver->is_active) {
            $fail('The driver account is not active.');

            return;
        }

        if ($driver->availability_status !== 'available') {
            $fail('The driver is not available at the moment.');
        }
    }
}
