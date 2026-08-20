<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AutomationAction extends Model
{
    /** @use HasFactory<\Database\Factories\AutomationActionFactory> */
    use HasFactory;

    protected $fillable = [
        'automation_id',
        'type',
        'target_id',
        'target_type',
        'payload',
        'sort',
    ];

    protected $casts = [
        'payload' => 'array',
        'sort' => 'integer',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
