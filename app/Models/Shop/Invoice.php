<?php

namespace App\Models\Shop;

use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'customer_id',
        'order_id',
        'status',
        'currency',
        'subtotal',
        'tax_amount',
        'total_amount',
        'due_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->number)) {
                $invoice->number = 'INV-'.random_int(100000, 999999);
            }
            if (empty($invoice->created_by)) {
                $invoice->created_by = auth()->id();
            }
            if (empty($invoice->status)) {
                $invoice->status = InvoiceStatus::DRAFT;
            }
            if (empty($invoice->currency)) {
                $invoice->currency = CurrencyCode::USD;
            }
        });

        static::updating(function (Invoice $invoice) {
            // Recalculate totals when items change
            if ($invoice->isDirty(['subtotal', 'tax_amount'])) {
                $invoice->total_amount = $invoice->subtotal + $invoice->tax_amount;
            }
        });
    }
}
