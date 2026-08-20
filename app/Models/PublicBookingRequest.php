<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicBookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_reference',
        'type',
        'booking_kind',
        'service_id',
        'service_name',
        'assigned_admin_id',
        'assignment_source',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'guests',
        'rooms',
        'passengers',
        'adults',
        'children',
        'participants',
        'base_price',
        'check_in_date',
        'check_out_date',
        'reservation_date',
        'reservation_time',
        'pickup_date_time',
        'tour_date',
        'tour_schedule',
        'pickup_address',
        'dropoff_address',
        'notes',
        'approved_at',
        'cancelled_at',
        'decided_by_admin_id',
        'decision_notes',
        'remote_booking_status',
        'remote_source_platform',
        'remote_source_label',
        'remote_external_id',
        'remote_response',
        'remote_error',
        'payment_provider',
        'payment_status',
        'payment_amount_cents',
        'payment_order',
        'payment_reference',
        'payment_paid_at',
        'payment_raw',
    ];

    protected $casts = [
        'guests' => 'integer',
        'rooms' => 'integer',
        'passengers' => 'integer',
        'adults' => 'integer',
        'children' => 'integer',
        'participants' => 'integer',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'reservation_date' => 'date',
        'pickup_date_time' => 'datetime',
        'tour_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'remote_response' => 'array',
        'payment_paid_at' => 'datetime',
        'payment_raw' => 'array',
    ];

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function decidedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'decided_by_admin_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PublicBookingRequestItem::class);
    }

    public function approve(?User $admin = null, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => 'approved',
            'approved_at' => now(),
            'cancelled_at' => null,
            'decided_by_admin_id' => $admin?->id,
            'decision_notes' => $notes,
        ])->save();

        if (in_array($this->type, ['transfer', 'taxi'], true)) {
            $this->materializeAsBooking();
        }
    }

    public function cancel(?User $admin = null, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'decided_by_admin_id' => $admin?->id,
            'decision_notes' => $notes,
        ])->save();
    }

    public function materializeAsBooking(): ?Booking
    {
        if (! in_array($this->type, ['transfer', 'taxi', 'restaurant'], true)) {
            return null;
        }

        if ($this->booking()->exists()) {
            return $this->booking;
        }

        $bookingDate = match ($this->type) {
            'transfer', 'taxi' => $this->pickup_date_time
                ?? ($this->tour_date ? CarbonImmutable::parse($this->tour_date.' '.($this->tour_schedule ?? '00:00')) : now()),
            'restaurant' => $this->reservation_date
                ? (str_contains($this->reservation_date, ' ')
                    ? CarbonImmutable::parse($this->reservation_date)
                    : CarbonImmutable::parse($this->reservation_date.' '.($this->reservation_time ?? '12:00')))
                : now(),
            default => now(),
        };

        $bookingType = match ($this->type) {
            'transfer', 'taxi' => 'Taxi',
            'restaurant' => 'Restaurant',
            default => 'Other',
        };

        $specialRequests = match ($this->type) {
            'transfer', 'taxi' => $this->pickup_address.' → '.$this->dropoff_address.($this->notes ? '. '.$this->notes : ''),
            'restaurant' => $this->notes ?? '',
            default => $this->notes ?? '',
        };

        $booking = Booking::create([
            'booking_reference' => $this->request_reference,
            'user_id' => $this->assigned_admin_id ?? 1,
            'booking_type' => $bookingType,
            'booking_date' => $bookingDate,
            'status' => $this->status === 'cancelled' ? 'Cancelled' : ($this->status === 'approved' ? 'Confirmed' : 'Pending'),
            'total_price' => $this->payment_amount_cents ? ($this->payment_amount_cents / 100) : ($this->base_price ?? 0),
            'discount_amount' => 0,
            'payment_status' => $this->payment_status === 'paid' ? 'Paid' : ($this->payment_status === 'failed' ? 'Failed' : 'Pending'),
            'special_requests' => $specialRequests,
            'cancellation_reason' => null,
            'last_updated' => now(),
        ]);

        if ($this->type === 'transfer' || $this->type === 'taxi') {
            TaxiBooking::create([
                'booking_id' => $booking->id,
                'taxi_service_id' => $this->service_id,
                'pickup_date_time' => $bookingDate,
                'type_of_booking' => 'one_way',
                'passenger_count' => $this->passengers ?? $this->adults ?? 1,
                'status' => $this->status === 'cancelled' ? 'Cancelled' : ($this->status === 'approved' ? 'Confirmed' : 'Pending'),
            ]);
        }

        if ($this->type === 'restaurant') {
            RestaurantBooking::create([
                'booking_id' => $booking->id,
                'restaurant_id' => $this->service_id,
                'table_id' => null,
                'reservation_date' => $this->reservation_date,
                'reservation_time' => $this->reservation_time,
                'number_of_guests' => $this->guests ?? 1,
                'duration' => 120,
            ]);
        }

        return $booking;
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'request_reference', 'booking_reference');
    }
}
