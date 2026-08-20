<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationSync extends Model
{
    use HasFactory;

    protected $table = 'reservation_sync';

    protected $fillable = [
        'reservation_id',
        'source',
        'job_name',
        'payload_hash',
        'status',
        'error_message',
        'meta',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
