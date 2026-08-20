<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Kontrakt dla zasobu wynajmowanego (Rentable).
 *
 * Aplikacja oznacza swoj model traitem App\Concerns\IsRentable
 * (lub implementuje ten interfejs recznie). Rental + AvailabilitySlot uzywaja
 * polimorficznych relacji (rentable_type / rentable_id) by wskazywac na ten
 * model — bez hardcodow typu motocykl. Tym samym pakiet jest reusowalny dla
 * dowolnego zasobu (motocykl, sala, sprzet, lokal).
 */
interface Rentable
{
    /**
     * Identyfikator (zazwyczaj $this->getKey()).
     */
    public function getRentableId(): int|string;

    /**
     * Czytelna nazwa wynajmowanego zasobu (do listy admina, emaila itp.).
     */
    public function getRentableName(): string;

    /**
     * Slug uzywany w URL-ach (np. /motocykle/ducati-desert-x).
     */
    public function getRentableSlug(): string;
}
