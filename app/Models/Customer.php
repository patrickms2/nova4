<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'latepoint_customer_id',
        'wordpress_user_id',
        'woo_customer_user_id',
        'source_updated_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'latepoint_customer_id' => 'integer',
            'wordpress_user_id' => 'integer',
            'woo_customer_user_id' => 'integer',
            'source_updated_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $customer): void {
            if (blank($customer->uuid)) {
                $customer->uuid = (string) Str::uuid();
            }
        });
    }
}
