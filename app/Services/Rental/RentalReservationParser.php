<?php

namespace App\Services\Rental;

use App\Models\RentalGuest;
use App\Models\RentalProperty;
use App\Models\RentalReservation;
use App\Models\RentalTimelineEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class RentalReservationParser
{
    public function parse(array $payload): RentalReservation
    {
        $guest = $this->resolveGuest($payload['guest'] ?? $payload);

        $property = $this->resolveProperty($payload['property'] ?? $payload['villa'] ?? $payload);

        $reservation = RentalReservation::create([
            'rental_property_id' => $property?->id,
            'guest_id' => $guest->id,
            'channel' => $payload['channel'] ?? $this->guessChannel($payload),
            'reference_code' => $payload['reference_code'] ?? ($payload['code'] ?? null),
            'check_in' => Carbon::parse($payload['check_in'] ?? $payload['start_date'] ?? now()->addDay()),
            'check_out' => Carbon::parse($payload['check_out'] ?? $payload['end_date'] ?? now()->addDays(2)),
            'adults' => $payload['adults'] ?? 1,
            'children' => $payload['children'] ?? 0,
            'amount' => $this->money($payload['amount'] ?? $payload['total'] ?? 0),
            'channel_commission' => $this->money($payload['channel_commission'] ?? $payload['commission'] ?? 0),
            'management_commission' => $this->money($payload['management_commission'] ?? 0),
            'cleaning_fee' => $this->money($payload['cleaning_fee'] ?? 0),
            'payout' => $this->money($payload['payout'] ?? $payload['owner_payout'] ?? 0),
            'status' => $payload['status'] ?? 'confirmed',
            'raw_payload' => $payload,
            'parsed_at' => now(),
        ]);

        RentalTimelineEvent::record($reservation, 'reservation_created', 'Reserva importada', 'Canal: '.$reservation->channel);

        RentalSettlementCalculator::for($reservation)->calculate();

        return $reservation;
    }

    public function parseFromRaw(string $source, string $raw): ?RentalReservation
    {
        $payload = $this->extractFromText($source, $raw);

        return empty($payload) ? null : $this->parse($payload);
    }

    public function parseFromCsv(string $path): ?RentalReservation
    {
        if (! Storage::exists($path) && ! file_exists($path)) {
            return null;
        }

        $realPath = file_exists($path) ? $path : Storage::path($path);
        $handle = fopen($realPath, 'r');
        if ($handle === false) {
            return null;
        }

        $headers = fgetcsv($handle);
        $row = fgetcsv($handle);
        fclose($handle);

        if ($row === false || $headers === false) {
            return null;
        }

        $data = array_combine($headers, $row);

        return $data ? $this->parse($data) : null;
    }

    private function resolveGuest(array $data): RentalGuest
    {
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;

        $query = RentalGuest::query();
        if ($email) {
            $query->where('email', $email);
        } elseif ($phone) {
            $query->where('phone', $phone);
        }

        return $query->first() ?? RentalGuest::create([
            'first_name' => $data['first_name'] ?? $data['name'] ?? 'Huésped',
            'last_name' => $data['last_name'] ?? null,
            'email' => $email,
            'phone' => $phone,
            'country' => $data['country'] ?? null,
            'document_number' => $data['document_number'] ?? null,
        ]);
    }

    private function resolveProperty(array $data): ?RentalProperty
    {
        $code = $data['property_code'] ?? $data['villa_code'] ?? $data['code'] ?? null;
        $name = $data['property_name'] ?? $data['villa_name'] ?? $data['name'] ?? null;

        if ($code) {
            return RentalProperty::where('code', $code)->first();
        }

        if ($name) {
            return RentalProperty::where('name', $name)->first();
        }

        return RentalProperty::first();
    }

    private function guessChannel(array $payload): string
    {
        $raw = json_encode($payload);
        if (str_contains($raw, 'airbnb') || str_contains($raw, 'Airbnb')) {
            return 'airbnb';
        }
        if (str_contains($raw, 'booking') || str_contains($raw, 'Booking.com')) {
            return 'booking';
        }
        if (str_contains($raw, 'guesty')) {
            return 'guesty';
        }

        return 'direct';
    }

    private function money(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $cleaned = preg_replace('/[^0-9.,]/', '', (string) $value);
        $cleaned = str_replace(',', '.', $cleaned);

        return (float) $cleaned;
    }

    private function extractFromText(string $source, string $raw): array
    {
        if ($source === 'bayside' || str_contains(strtolower($raw), 'bayside')) {
            return $this->extractFromBaysideEmail($raw);
        }

        return [
            'channel' => $source,
            'first_name' => 'Huésped',
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'amount' => 0,
            'reference_code' => 'IMP-' . strtoupper(substr(md5($raw), 0, 8)),
            'raw' => $raw,
        ];
    }

    private function extractFromBaysideEmail(string $raw): array
    {
        $raw = preg_replace('/\s+/u', ' ', $raw);

        $data = ['channel' => 'bayside'];

        if (preg_match('/(?:name|nombre|huésped|guest)\s*[:\-]?\s*([A-Z][A-Za-zÀ-ÿ\s]+?)(?=\s{2,}|\n|<|\r|$)/iu', $raw, $match)) {
            $name = trim($match[1]);
            $parts = explode(' ', $name, 2);
            $data['first_name'] = $parts[0];
            $data['last_name'] = $parts[1] ?? null;
        } else {
            $data['first_name'] = 'Huésped';
        }

        if (preg_match('/[\w.\-]+@[\w.\-]+\.\w+/', $raw, $match)) {
            $data['email'] = $match[0];
        }

        if (preg_match('/(?:tel|phone|teléfono|móvil|mobile)\s*[:\-]?\s*([+\d\s().\-]{6,})/iu', $raw, $match)) {
            $data['phone'] = trim($match[1]);
        }

        $data['check_in'] = $this->extractDate($raw, ['check.in', 'check-in', 'entrada', 'llegada', 'arrival', 'from', 'desde']);
        $data['check_out'] = $this->extractDate($raw, ['check.out', 'check-out', 'salida', 'departure', 'to', 'hasta']);

        $data['amount'] = $this->extractMoney($raw, ['total', 'importe', 'amount', 'precio', 'price', 'paid']) ?? 0;
        $data['channel_commission'] = $this->extractMoney($raw, ['commission', 'comisión', 'comision']) ?? 0;

        if (preg_match('/(?:adults?|adultos?)\s*[:\-]?\s*(\d+)/iu', $raw, $match)) {
            $data['adults'] = (int) $match[1];
        }
        if (preg_match('/(?:children|kids|niños|infantil)\s*[:\-]?\s*(\d+)/iu', $raw, $match)) {
            $data['children'] = (int) $match[1];
        }
        if (! isset($data['adults']) && preg_match('/(?:guests?|huéspedes?|viajeros?)\s*[:\-]?\s*(\d+)/iu', $raw, $match)) {
            $data['adults'] = (int) $match[1];
        }

        if (preg_match('/(?:confirmation|reservation|booking|reference|ref|código)\s*[#:\-]?\s*([A-Z0-9\-]{4,})/iu', $raw, $match)) {
            $data['reference_code'] = $match[1];
        } else {
            $data['reference_code'] = 'BAYSIDE-' . strtoupper(substr(md5($raw), 0, 8));
        }

        $data['property_name'] = 'Casa El Patio';
        $data['raw'] = $raw;

        return $data;
    }

    private function extractDate(string $text, array $keywords): ?string
    {
        $pattern = implode('|', array_map(fn ($keyword) => preg_quote($keyword, '/'), $keywords));

        if (preg_match('/(?:' . $pattern . ')\s*[:\-]?\s*(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}|\d{4}[\/\-.]\d{1,2}[\/\-.]\d{1,2}|\d{1,2}\s+[A-Za-zÀ-ÿ]+\s+\d{4})/iu', $text, $match)) {
            try {
                return Carbon::parse($match[1])->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        if (preg_match('/(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})/u', $text, $match)) {
            try {
                return Carbon::parse($match[1])->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function extractMoney(string $text, array $keywords): ?float
    {
        $pattern = implode('|', array_map(fn ($keyword) => preg_quote($keyword, '/'), $keywords));

        if (preg_match('/(?:' . $pattern . ')\s*[:\-]?\s*[€$£]?\s*([\d\s.,]+)/iu', $text, $match)) {
            return $this->money($match[1]);
        }

        if (preg_match('/[€$£]\s*([\d\s.,]+)/u', $text, $match)) {
            return $this->money($match[1]);
        }

        return null;
    }
}
