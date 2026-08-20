<?php

namespace App\Rules;

use App\Models\Trip;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TripIsPending implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $trip = Trip::find($value);

        if (! $trip) {
            $fail('The trip does not exist.');

            return;
        }

        if ($trip->status !== 'pending') {
            $fail('The trip is no longer available for acceptance.');
        }
    }
}
