<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RentalTimelineEvent extends Model
{
    /** @use HasFactory<\Database\Factories\RentalTimelineEventFactory> */
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'event_type',
        'title',
        'description',
        'meta',
        'occurred_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(Model $subject, string $eventType, string $title, ?string $description = null, array $meta = []): static
    {
        return static::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'meta' => $meta,
            'occurred_at' => now(),
        ]);
    }
}
