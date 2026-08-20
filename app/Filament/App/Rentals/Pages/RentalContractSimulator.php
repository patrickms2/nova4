<?php

namespace App\Filament\App\Rentals\Pages;

use App\Filament\App\Rentals\Rentals;
use App\Models\RentalReservation;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class RentalContractSimulator extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $navigationLabel = 'Simulador de contrato';

    protected static ?string $title = 'Simulador de contrato';

    protected static string|\UnitEnum|null $navigationGroup = 'Property OS';

    protected static ?string $cluster = Rentals::class;

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.rental-contract-simulator';

    public float $totalAmount = 1500;

    public float $channelCommission = 150;

    public int $adults = 2;

    public int $children = 0;

    public int $nights = 7;

    public float $cleaning = 90;

    public float $laundryPerGuest = 15;

    public float $welcomePack = 25;

    public float $damageWaiver = 20;

    public float $managerCommissionRate = 30;

    public function mount(): void
    {
        $reservationId = request()->integer('reservation');

        if ($reservationId === 0) {
            return;
        }

        $reservation = RentalReservation::query()->find($reservationId);

        if (! $reservation) {
            return;
        }

        $this->totalAmount = (float) $reservation->amount;
        $this->channelCommission = (float) $reservation->channel_commission;
        $this->adults = $reservation->adults;
        $this->children = $reservation->children;
        $this->nights = $reservation->nights();
        $this->cleaning = (float) $reservation->cleaning_fee;
        $this->managerCommissionRate = $reservation->amount > 0
            ? round(((float) $reservation->management_commission / (float) $reservation->amount) * 100, 2)
            : $this->managerCommissionRate;
    }

    public function getResultsProperty(): array
    {
        $guests = $this->adults + $this->children;
        $laundry = $this->laundryPerGuest * $guests;
        $services = $this->cleaning + $laundry + $this->welcomePack + $this->damageWaiver;
        $accommodation = max(0.0, $this->totalAmount - $services);
        $commissionableBase = max(0.0, $accommodation - $this->channelCommission);
        $managerCommission = $commissionableBase * ($this->managerCommissionRate / 100);
        $netOwner = $commissionableBase - $managerCommission;
        $totalCommissions = $this->channelCommission + $managerCommission;

        return [
            'guests' => $guests,
            'laundry' => $laundry,
            'services' => $services,
            'accommodation' => $accommodation,
            'commissionableBase' => $commissionableBase,
            'managerCommission' => $managerCommission,
            'netOwner' => $netOwner,
            'totalCommissions' => $totalCommissions,
        ];
    }
}
