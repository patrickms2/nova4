<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Walidacja POST /api/rentals.
 *
 * Wzorzec portu: blizne-art-cms StoreReservationRequest.
 *
 * Adaptacja:
 *  - rentable identyfikowany przez slug/UUID/id (resolwowany w kontrolerze)
 *  - daty start/end (overlap weryfikuje RentalService::book w transakcji)
 *  - qty zamiast guests, brak max_guests (model ekskluzywny)
 *  - gdpr_consent: accepted (RODO twardy gate)
 *
 * @see KML-0057 (E2)
 */
class StoreRentalRequest extends FormRequest
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
            // Rentable: slug lub PK (UUID/id) — kontroler resolwuje
            'rentable' => ['required', 'string', 'max:255'],

            // Termin
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],

            // Klient
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:6', 'max:30'],
            'message' => ['nullable', 'string', 'max:1000'],

            // Parametry rezerwacji
            'qty' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'rental_type_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'total_amount' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'locale' => ['sometimes', 'string', 'in:pl,en'],

            // RODO — twardy gate
            'gdpr_consent' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rentable.required' => 'Identyfikator zasobu jest wymagany.',
            'start_date.required' => 'Data początkowa jest wymagana.',
            'start_date.date_format' => 'Format daty: YYYY-MM-DD.',
            'end_date.required' => 'Data końcowa jest wymagana.',
            'end_date.date_format' => 'Format daty: YYYY-MM-DD.',
            'end_date.after_or_equal' => 'Data końcowa nie może być wcześniejsza niż początkowa.',
            'name.required' => 'Imię i nazwisko jest wymagane.',
            'name.min' => 'Imię i nazwisko musi mieć co najmniej 2 znaki.',
            'email.required' => 'Adres email jest wymagany.',
            'email.email' => 'Podaj poprawny adres email.',
            'phone.required' => 'Numer telefonu jest wymagany.',
            'phone.min' => 'Numer telefonu jest za krótki.',
            'qty.integer' => 'Liczba sztuk musi być liczbą całkowitą.',
            'qty.min' => 'Minimalna liczba sztuk to 1.',
            'gdpr_consent.required' => 'Zgoda RODO jest wymagana.',
            'gdpr_consent.accepted' => 'Zgoda RODO jest wymagana.',
        ];
    }
}
