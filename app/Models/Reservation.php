<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'latepoint_booking_id',
        'latepoint_order_id',
        'latepoint_order_item_id',
        'latepoint_transaction_id',
        'woo_order_id',
        'woo_order_item_id',
        'intent_key',
        'service_id',
        'service_name',
        'agent_id',
        'agent_name',
        'language_code',
        'booking_date',
        'booking_time',
        'booking_starts_at',
        'booking_ends_at',
        'attendees',
        'adults',
        'children',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total',
        'currency',
        'booking_status',
        'payment_status',
        'confirmation_code',
        'invoice_url',
        'woo_admin_url',
        'latepoint_admin_url',
        'internal_status',
        'internal_notes',
        'has_incident',
        'sync_status',
        'synced_at',
        'source_updated_at',
        'source_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'booking_starts_at' => 'datetime',
            'booking_ends_at' => 'datetime',
            'total' => 'decimal:2',
            'has_incident' => 'boolean',
            'synced_at' => 'datetime',
            'source_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $reservation): void {
            if (blank($reservation->uuid)) {
                $reservation->uuid = (string) Str::uuid();
            }
        });
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(ReservationSync::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ReservationNote::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
