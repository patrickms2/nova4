<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AutomationCondition extends Model
{
    /** @use HasFactory<\Database\Factories\AutomationConditionFactory> */
    use HasFactory;

    protected $fillable = [
        'automation_id',
        'type',
        'source_id',
        'source_type',
        'operator',
        'value',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
