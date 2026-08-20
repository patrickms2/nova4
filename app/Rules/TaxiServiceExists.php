<?php

namespace App\Rules;

use App\Models\TaxiService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TaxiServiceExists implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $taxiService = TaxiService::find($value);

        if (! $taxiService) {
            $fail('The selected taxi service does not exist.');

            return;
        }

        if (! $taxiService->is_active) {
            $fail('The selected taxi service is not currently active.');
        }
    }
}
