<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'source',
        'external_id',
        'reservation_id',
        'customer_id',
        'woo_order_id',
        'latepoint_order_id',
        'number',
        'status',
        'total',
        'currency',
        'invoice_url',
        'issued_at',
        'source_updated_at',
        'meta',
        'source_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'woo_order_id' => 'integer',
            'latepoint_order_id' => 'integer',
            'total' => 'decimal:2',
            'issued_at' => 'datetime',
            'source_updated_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invoice): void {
            if (blank($invoice->uuid)) {
                $invoice->uuid = (string) Str::uuid();
            }
        });
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
