<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TrackingReplayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'uniqueId' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'embed' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'uniqueId.max' => 'El identificador del dispositivo es demasiado largo.',
            'from.date' => 'La fecha inicial del replay no es válida.',
            'to.date' => 'La fecha final del replay no es válida.',
            'to.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
        ];
    }
}
