<?php

namespace App\Filament\App\Rentals\Pages;

use App\Filament\App\Rentals\Rentals;
use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\Automation;
use App\Models\Device;
use App\Models\DomoticsEvent;
use App\Models\Property;
use App\Models\RentalDocument;
use App\Models\RentalExpense;
use App\Models\RentalIncident;
use App\Models\RentalInventoryItem;
use App\Models\RentalPayment;
use App\Models\RentalReservation;
use App\Models\RentalSettlement;
use App\Models\RentalTimelineEvent;
use App\Models\Task;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class CasaElPatioDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Casa El Patio';

    protected static ?string $title = 'Casa El Patio';

    protected static string|\UnitEnum|null $navigationGroup = '';

    /*protected static ?string $cluster = Rentals::class;*/

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.casa-el-patio-dashboard-cards';

    public array $kpis = [];

    public array $latestReservations = [];

    public array $latestExpenses = [];

    public array $latestInventory = [];

    public array $latestDocuments = [];

    public array $latestTasks = [];

    public array $sectionTotals = [];

    public array $domoticTotals = [];

    public array $latestDomoticEvents = [];

    public array $accessOperations = [];

    public array $timeline = [];

    public function mount(): void
    {
        $currentMonth = now()->startOfMonth();
        $nextMonth = now()->endOfMonth();

        $monthlyReservations = RentalReservation::whereBetween('check_in', [$currentMonth, $nextMonth])
            ->where('status', 'confirmed')
            ->get();

        $monthlyExpenses = RentalExpense::whereBetween('expense_date', [$currentMonth, $nextMonth]);
        $monthlyExpensesTotal = (float) $monthlyExpenses->sum('total_amount');

        $monthlySettlements = RentalSettlement::whereHas('reservation', function (Builder $query) use ($currentMonth, $nextMonth): void {
            $query->whereBetween('check_in', [$currentMonth, $nextMonth])
                ->where('status', 'confirmed');
        })->get();

        $pendingPayments = RentalPayment::where('status', 'pending')->sum('amount');
        $pendingExpenses = RentalExpense::where('status', 'pending')->sum('total_amount');

        $property = Property::query()->where('slug', 'casa-el-patio')->orWhere('name', 'Casa El Patio')->first();
        $propertyId = $property?->id;

        $nextArrival = RentalReservation::with(['person', 'guest', 'accessGrants.credentials', 'accessGrants.accessPoints'])
            ->when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))
            ->where('check_in', '>=', today())
            ->orderBy('check_in')
            ->first();

        $nextDeparture = RentalReservation::with(['person', 'guest'])
            ->when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))
            ->where('check_out', '>=', today())
            ->orderBy('check_out')
            ->first();

        $openIncidents = RentalIncident::where('status', 'open')->count();
        $inventoryValue = RentalInventoryItem::sum('purchase_value');

        $ownerPayouts = (float) $monthlySettlements->sum(fn (RentalSettlement $settlement): float => (float) ($settlement->real_payout ?? $settlement->estimated_net));

        $this->kpis = [
            'billing' => (float) $monthlyReservations->sum('amount'),
            'accommodation' => (float) $monthlySettlements->sum('accommodation_amount'),
            'netOwner' => (float) $monthlySettlements->sum('estimated_net'),
            'realPayout' => (float) $monthlySettlements->sum('real_payout'),
            'difference' => (float) $monthlySettlements->sum('difference'),
            'channelCommission' => (float) $monthlySettlements->sum('channel_commission_amount'),
            'managementCommission' => (float) $monthlySettlements->sum('manager_commission_amount'),
            'totalCommissions' => (float) $monthlySettlements->sum('channel_commission_amount') + (float) $monthlySettlements->sum('manager_commission_amount'),
            'services' => (float) $monthlySettlements->sum('services_amount'),
            'operatingExpenses' => $monthlyExpensesTotal,
            'cashFlow' => (float) $monthlyReservations->sum('amount') - $ownerPayouts - $monthlyExpensesTotal,
            'occupancy' => $this->occupancy($currentMonth, $nextMonth),
            'adr' => $this->adr($monthlyReservations),
            'revpar' => $this->revpar($monthlyReservations, $currentMonth, $nextMonth),
            'pendingPayments' => (float) $pendingPayments,
            'pendingExpenses' => (float) $pendingExpenses,
            'inventoryValue' => (float) $inventoryValue,
            'openIncidents' => $openIncidents,
            'pendingTasks' => Task::where('is_completed', false)->count(),
        ];

        $this->kpis['nextArrival'] = $nextArrival ? [
            'guest' => $nextArrival->guest?->fullName(),
            'date' => $nextArrival->check_in->format('d M'),
        ] : null;

        $this->kpis['nextDeparture'] = $nextDeparture ? [
            'guest' => $nextDeparture->guest?->fullName(),
            'date' => $nextDeparture->check_out->format('d M'),
        ] : null;

        $this->latestReservations = RentalReservation::with('guest', 'rentalProperty')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn (RentalReservation $r) => [
                'id' => $r->id,
                'guest' => $r->guest?->fullName(),
                'property' => $r->rentalProperty?->name,
                'check_in' => $r->check_in->format('d M'),
                'check_out' => $r->check_out->format('d M'),
                'amount' => $r->amount,
                'channel' => $r->channel,
            ])
            ->toArray();

        $this->latestExpenses = RentalExpense::with('rentalProperty')
            ->orderBy('expense_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn (RentalExpense $e) => [
                'id' => $e->id,
                'description' => $e->description,
                'total_amount' => $e->total_amount,
                'expense_date' => $e->expense_date->format('d M'),
                'status' => $e->status,
            ])
            ->toArray();

        $this->latestInventory = RentalInventoryItem::with('rentalProperty')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn (RentalInventoryItem $i) => [
                'id' => $i->id,
                'category' => $i->category,
                'location' => $i->location,
                'purchase_value' => $i->purchase_value,
                'status' => $i->status,
            ])
            ->toArray();

        $this->latestDocuments = RentalDocument::query()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn (RentalDocument $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'category' => $d->category,
                'expiry_date' => $d->expiry_date?->format('d M'),
            ])
            ->toArray();

        $this->latestTasks = Task::where('is_completed', false)
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'due_date' => $t->due_date?->format('d M'),
                'status' => $t->status,
            ])
            ->toArray();

        $this->latestDomoticEvents = DomoticsEvent::query()
            ->with('accessPoint')
            ->when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn (DomoticsEvent $e) => [
                'time' => $e->created_at->diffForHumans(),
                'title' => $e->accessPoint?->name ?? 'Propiedad',
                'type' => $e->event_type->getLabel(),
            ])
            ->toArray();

        $this->sectionTotals = [
            'reservations' => $monthlyReservations->count(),
            'expenses' => RentalExpense::count(),
            'inventory' => RentalInventoryItem::count(),
            'documents' => RentalDocument::count(),
            'tasks' => Task::where('is_completed', false)->count(),
            'incidents' => $openIncidents,
        ];

        $this->domoticTotals = [
            'devices' => Device::when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))->count(),
            'accessPoints' => AccessPoint::when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))->count(),
            'accessGrants' => AccessGrant::when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))->active()->count(),
            'automations' => Automation::when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))->where('is_active', true)->count(),
            'events' => DomoticsEvent::when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))->count(),
            'properties' => $property === null ? 0 : 1,
        ];

        $devices = Device::query()->when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))->get(['id', 'name', 'status']);
        $points = AccessPoint::query()->with('device:id,status')->when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))->get(['id', 'property_id', 'device_id', 'name', 'is_active']);
        $currentReservation = RentalReservation::with(['person', 'guest'])->when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))->where('status', 'confirmed')->whereDate('check_in', '<=', today())->whereDate('check_out', '>=', today())->first();
        $nextGrant = $nextArrival?->accessGrants->first();
        $this->accessOperations = [
            'operational' => $devices->doesntContain(fn (Device $device): bool => $device->status?->value === 'offline'),
            'online' => $devices->where('status.value', 'online')->count(),
            'offline' => $devices->where('status.value', 'offline')->count(),
            'points' => $points->map(fn (AccessPoint $point): array => ['name' => $point->name, 'online' => $point->device?->status?->value === 'online', 'active' => $point->is_active])->all(),
            'activeGrants' => $this->domoticTotals['accessGrants'],
            'currentGuest' => $currentReservation?->person?->display_name ?? $currentReservation?->guest?->fullName(),
            'currentUntil' => $currentReservation?->check_out?->format('d M Y'),
            'nextGuest' => $nextArrival?->person?->display_name ?? $nextArrival?->guest?->fullName(),
            'nextArrival' => $nextArrival?->check_in?->format('d M Y'),
            'personLinked' => $nextArrival?->person_id !== null,
            'grantCreated' => $nextGrant !== null,
            'credentialReady' => $nextGrant?->credentials->contains(fn ($credential): bool => $credential->isValidAt()) ?? false,
            'pointsAssigned' => $nextGrant?->accessPoints->isNotEmpty() ?? false,
            'alerts' => $devices->where('status.value', 'offline')->count(),
        ];

        $this->timeline = RentalTimelineEvent::with('subject')
            ->orderBy('occurred_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn (RentalTimelineEvent $e) => [
                'time' => $e->occurred_at->diffForHumans(),
                'title' => $e->title,
                'type' => $e->event_type,
            ])
            ->toArray();
    }

    private function occupancy($start, $end): float
    {
        $totalNights = $end->diffInDays($start);

        $bookedNights = RentalReservation::where('status', 'confirmed')
            ->where(function (Builder $query) use ($start, $end): void {
                $query->whereBetween('check_in', [$start, $end])
                    ->orWhereBetween('check_out', [$start, $end]);
            })
            ->get()
            ->sum(fn (RentalReservation $r) => min($end, $r->check_out)->diffInDays(max($start, $r->check_in)));

        return $totalNights > 0 ? round(($bookedNights / $totalNights) * 100, 1) : 0.0;
    }

    private function adr($reservations): float
    {
        $count = $reservations->count();

        return $count > 0 ? round((float) $reservations->sum('amount') / $count, 2) : 0.0;
    }

    private function revpar($reservations, $start, $end): float
    {
        $totalNights = $end->diffInDays($start);

        return $totalNights > 0 ? round((float) $reservations->sum('amount') / $totalNights, 2) : 0.0;
    }
}
