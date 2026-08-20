<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NovaWhatsappChannel extends Model
{
    protected $fillable = [
        'nova_business_id',
        'name',
        'slug',
        'type',
        'status',
        'settings',
        'phone_number_id',
        'phone_number',
        'created_at',
        'updated_at',
        'business_account_id',
        'webhook_url',
        'credentials',
    ];

    protected $casts = [
        'settings' => 'array',
        'credentials' => 'array',
    ];

        public function business(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'nova_business_id');
    }


}
