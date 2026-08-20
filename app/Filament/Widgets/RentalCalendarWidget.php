<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Illuminate\Support\Carbon;
use App\Models\AvailabilitySlot;
use App\Models\Rental;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\RentalExpense;
use App\Models\RentalIncident;
use App\Models\RentalReservation;
use App\Models\Task;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;

/**
 * Widget kalendarza pokazujacy:
 *  - aktywne rezerwacje (Rental — kolory wg statusu)
 *  - blokady dostepnosci (AvailabilitySlot — szare bloki)
 *
 * Widget korzysta z saade/filament-fullcalendar (peer dependency
 * dodawana do composer.json pakietu).
 *
 * Aplikacja moze nadpisac widget wlasnym (np. dodajac filtr per-rentable),
 * dziedziczac po RentalCalendarWidget i nadpisujac fetchEvents.
 *
 * @see KML-0054 (D4)
 */
class RentalCalendarWidget extends FullCalendarWidget
{
    public function fetchEvents(array $info): array
    {
        $start = $info['start'] ?? now()->subDays(30)->toDateString();
        $end = $info['end'] ?? now()->addDays(60)->toDateString();

        $events = [];


        $events = collect();
        // Rezerwacje (aktywne)
        RentalReservation::query()
            ->where('status', 'confirmed')
            ->where('check_in', '<=', $end)
            ->where('check_out', '>=', $start)
            ->get()
            ->each(function (RentalReservation $r) use (&$events) {
                $title = $this->buildEventTitle($r);
                $events[] = EventData::make()
                    ->id('rental-'.$r->id)
                    ->title($title)
                    ->start($r->check_in)
                    ->end(Carbon::parse($r->check_out)->addDay()->toDateString()) // FullCalendar end exclusive
                    ->allDay(true) // KML-0055: bez prefixu "00" (czas startu) na pasku
                    ->backgroundColor($this->statusColor($r->status))
                    ->borderColor($this->statusColor($r->status))
                    ->extendedProps([
                        'channel' =>  $r->channel,
                        'status' => $r->status,
                        'email' => $r->email,
                    ])
                    ->extraProperties(['classNames' => ['fc-rental-event']])
                    ->toArray();
            });

        // Blokady
        AvailabilitySlot::query()
            ->blocked()
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->get()
            ->each(function (AvailabilitySlot $s) use (&$events) {
                // KML-0054: nazwa zasobu na pasku — bypass globalnych scope'ow
                // (np. TenantScope w aplikacji), zeby pobrac motocykl niezaleznie od kontekstu.
                $rentableName = $this->resolveRentableName($s->rentable_type, $s->rentable_id);
                $titleParts = [];
                if ($rentableName !== null) {
                    $titleParts[] = $rentableName;
                }
                $titleParts[] = 'Blokada'.($s->reason ? ': '.$s->reason : '');

                $events[] = EventData::make()
                    ->id('slot-'.$s->id)
                    ->title(implode(' — ', $titleParts))
                    ->start($s->start_date)
                    ->end(Carbon::parse($s->end_date)->addDay()->toDateString())
                    ->allDay(true) // KML-0055: bez prefixu "00" (czas startu) na pasku
                    ->backgroundColor('#9ca3af')
                    ->borderColor('#6b7280')
                    ->extendedProps([
                        'kind' => 'slot',
                        'reason' => $s->reason,
                        'rentable_name' => $rentableName,
                    ])
                    ->extraProperties(['classNames' => ['fc-slot-event']]) // KML-0054: cursor:pointer
                    ->toArray();
            });

        return $events;
    }

    /**
     * Buduje tytul eventu kalendarza dla rezerwacji.
     *
     * Format: "{name} — {motocykl} — {kwota} {waluta} — {status}"
     * (KML-0042 — kafelek pokazuje cene i nazwe motocykla)
     */
    protected function buildEventTitle(RentalReservation $r): string
    {
        $parts = [trim($r->name ?? 'Rezerwacja')];

        $rentable = $r->rentable;
        if ($rentable !== null) {
            $rentableName = $rentable->name ?? $rentable->title ?? null;
            if (is_string($rentableName) && $rentableName !== '') {
                $parts[] = $rentableName;
            }
        }

        if (is_int($r->total_amount) && $r->total_amount > 0) {
            $amount = number_format($r->total_amount / 100, 0, ',', ' ');
            $parts[] = $amount.' '.($r->currency ?? 'PLN');
        }

        $parts[] = $this->statusLabel($r->status);

        return implode(' — ', $parts);
    }

    /**
     * Rezolwuje nazwe morphTo rentable z bypass globalnych scope'ow.
     *
     * Aplikacja moze nakladac scope'y (np. TenantScope) na model rentable —
     * domyslny `with('rentable')` morphTo wtedy zwraca null. Widget kalendarza
     * pokazuje rezerwacje/blokady bez wzgledu na tenant scope (KML-0054).
     */
    protected function resolveRentableName(?string $type, mixed $id): ?string
    {
        if (! is_string($type) || $type === '' || $id === null || ! class_exists($type)) {
            return null;
        }

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $type;
        $query = method_exists($model, 'newQueryWithoutScopes')
            ? $model->newQueryWithoutScopes()
            : $model->newQuery();

        $rentable = $query->find($id);
        if ($rentable === null) {
            return null;
        }

        $name = $rentable->name ?? $rentable->title ?? null;

        return is_string($name) && $name !== '' ? $name : null;
    }

    protected function statusColor(string $status): string
    {
        return match ($status) {
            'pending' => '#f59e0b',
            'confirmed' => '#3b82f6',
            'paid' => '#10b981',
            'cancelled' => '#ef4444',
            'expired' => '#6b7280',
            default => '#6b7280',
        };
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Oczekuje',
            'confirmed' => 'Potwierdzona',
            'paid' => 'Oplacona',
            'cancelled' => 'Anulowana',
            'expired' => 'Wygasla',
            default => $status,
        };
    }

    /**
     * Konfiguracja FullCalendar — m.in. initialDate z query parametru ?date=YYYY-MM-DD.
     *
     * Pozwala otworzyc kalendarz w miesiacu wskazanym przez link z innej strony
     * (np. przycisk "Kalendarz" w edycji rezerwacji — KML-0045).
     *
     * @return array<string, mixed>
     */
    public function config(): array
    {
        $cfg = parent::config();
        $date = request()->query('date');

        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $cfg['initialDate'] = $date;
        }

        return $cfg;
    }

    public function getFormSchema(): array
    {
        // Widget read-only — kalendarz pokazuje istniejace rezerwacje.
        // Tworzenie odbywa sie przez RentalResource D1.
        return [];
    }

    protected function headerActions(): array
    {
        return [];
    }

    protected function modalActions(): array
    {
        return [];
    }

    /**
     * Override onEventClick — przekierowuje na edycje rezerwacji lub blokady.
     *
     * Event id ma format "rental-{uuid}" lub "slot-{id}". Dla rezerwacji
     * przekierowujemy na RentalResource edit page; dla blokad — na
     * AvailabilitySlotResource edit page (KML-0054).
     *
     * Bez override, parent FullCalendarWidget::onEventClick mountowalby
     * ViewAction ktora czyta $this->record — co dla widgeta z DWOMA
     * modelami nie dziala (KML-0043).
     */
    public function onEventClick(array $event): void
    {
        $id = $event['event']['id'] ?? $event['id'] ?? null;

        if (! is_string($id)) {
            return;
        }

        if (str_starts_with($id, 'rental-')) {
            $uuid = substr($id, strlen('rental-'));
            $this->redirect(url('/admin/villas/'.$uuid.'/edit'));

            return;
        }

        if (str_starts_with($id, 'slot-')) {
            // KML-0054: klik w pasek blokady → edycja AvailabilitySlot
            $slotId = substr($id, strlen('slot-'));
            $this->redirect(url('/admin/availability-slots/'.$slotId.'/edit'));
        }
    }
}
