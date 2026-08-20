<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityFee extends Model
{
    use HasFactory;

    protected $fillable = ['community_id', 'person_id', 'property_id', 'concept', 'period', 'amount', 'due_date', 'status', 'paid_at', 'receipt_path', 'metadata'];

    protected function casts(): array
    {
        return ['period' => 'date', 'due_date' => 'date', 'paid_at' => 'datetime', 'amount' => 'decimal:2', 'metadata' => 'array'];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(CommunityProperty::class, 'property_id');
    }
}
