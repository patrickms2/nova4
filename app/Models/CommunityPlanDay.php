<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityPlanDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_plan_item_id',
        'day_of_week',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function planItem(): BelongsTo
    {
        return $this->belongsTo(CommunityPlanItem::class);
    }
}
