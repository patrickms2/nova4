<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NovaWhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'phone_number_id',
        'from_phone',
        'message_type',
        'message_text',
        'status',
        'nova_request_id',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function novaRequest(): BelongsTo
    {
        return $this->belongsTo(NovaRequest::class);
    }
}
