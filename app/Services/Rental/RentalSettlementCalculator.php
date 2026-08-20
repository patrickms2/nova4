<?php

namespace App\Services\Rental;

use App\Models\RentalComponent;
use App\Models\RentalReservation;
use App\Models\RentalSettlement;
use App\Models\RentalTimelineEvent;

class RentalSettlementCalculator
{
    public static function for(RentalReservation $reservation): self
    {
        return new self($reservation);
    }

    public function __construct(protected RentalReservation $reservation)
    {
    }

    public function calculate(): RentalSettlement
    {
        $reservation = $this->reservation->fresh(['rentalProperty']);
        $property = $reservation->rentalProperty;
        $rules = $property?->financial_settings ?? [];
        $guests = $reservation->adults + $reservation->children;

        $reservation->components()->delete();

        $totalPaid = (float) $reservation->amount;
        $channelCommission = (float) $reservation->channel_commission;

        $cleaning = (float) ($rules['cleaning_per_stay'] ?? 0);
        $laundry = (float) ($rules['laundry_per_guest'] ?? 0) * $guests;
        $welcome = (float) ($rules['welcome_pack'] ?? 0);
        $damage = (float) ($rules['damage_waiver'] ?? 0);

        $serviceFees = $cleaning + $laundry + $welcome + $damage;
        $accommodationAmount = max(0.0, $totalPaid - $serviceFees);

        $this->component('accommodation', 'Alojamiento', $accommodationAmount, isIncome: true, generatesCommission: true);

        if ($channelCommission > 0) {
            $this->component('channel_commission', 'Comisión del canal', -$channelCommission, isExpense: true, generatesCommission: false);
        }

        if ($cleaning > 0) {
            $this->component('cleaning', 'Limpieza', $cleaning, isIncome: true, generatesCommission: false, providerName: $rules['cleaning_provider'] ?? null);
        }

        if ($laundry > 0) {
            $this->component('laundry', 'Lavandería', $laundry, isIncome: true, generatesCommission: false);
        }

        if ($welcome > 0) {
            $this->component('welcome_pack', 'Welcome Pack', $welcome, isIncome: true, generatesCommission: false);
        }

        if ($damage > 0) {
            $this->component('damage_waiver', 'Damage Waiver', $damage, isIncome: true, generatesCommission: false);
        }

        $commissionableBase = max(0.0, $accommodationAmount - $channelCommission);

        $managerRate = (float) ($rules['manager_commission_rate'] ?? 0) / 100;
        $managerCommission = $commissionableBase * $managerRate;
        if ($managerCommission > 0) {
            $this->component('manager_commission', 'Comisión del gestor', -$managerCommission, isExpense: true, generatesCommission: false, providerName: $rules['manager_name'] ?? null);
        }

        $estimatedNet = $commissionableBase - $managerCommission;

        $realPayout = $reservation->payout;
        $difference = $realPayout > 0 ? $realPayout - $estimatedNet : null;

        $settlement = RentalSettlement::updateOrCreate(
            ['rental_reservation_id' => $reservation->id],
            [
                'status' => 'estimated',
                'accommodation_amount' => $accommodationAmount,
                'channel_commission_amount' => $channelCommission,
                'commissionable_base' => $commissionableBase,
                'manager_commission_amount' => $managerCommission,
                'services_amount' => $serviceFees,
                'estimated_net' => $estimatedNet,
                'real_payout' => $realPayout > 0 ? $realPayout : null,
                'difference' => $difference,
            ]
        );

        RentalTimelineEvent::record(
            $reservation,
            'settlement_calculated',
            'Liquidación estimada',
            'Neto estimado: €'.number_format($estimatedNet, 2)
        );

        return $settlement;
    }

    protected function component(
        string $type,
        string $label,
        float $amount,
        bool $isIncome = false,
        bool $isExpense = false,
        bool $generatesCommission = false,
        ?string $providerName = null
    ): RentalComponent {
        return RentalComponent::create([
            'rental_reservation_id' => $this->reservation->id,
            'type' => $type,
            'label' => $label,
            'amount' => $amount,
            'is_income' => $isIncome,
            'is_expense' => $isExpense,
            'generates_commission' => $generatesCommission,
            'provider_name' => $providerName,
            'status' => 'estimated',
            'sort_order' => 0,
        ]);
    }
}
