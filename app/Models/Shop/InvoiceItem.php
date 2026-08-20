<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            // Calculate total price
            $subtotal = $item->quantity * $item->unit_price;
            $taxAmount = $subtotal * ($item->tax_rate / 100);
            $item->total_price = $subtotal + $taxAmount;
        });

        static::saved(function (InvoiceItem $item) {
            // Update invoice totals
            $invoice = $item->invoice;
            if ($invoice) {
                $subtotal = $invoice->invoiceItems()->sum('total_price');
                $taxAmount = $invoice->invoiceItems()->sum(function ($item) {
                    return ($item->quantity * $item->unit_price) * ($item->tax_rate / 100);
                });

                $invoice->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                ]);
            }
        });
    }
}
