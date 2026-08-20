<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'source',
        'external_id',
        'name',
        'status',
        'description',
        'price',
        'currency',
        'meta',
        'source_updated_at',
        'source_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'meta' => 'array',
            'source_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $service): void {
            if (blank($service->uuid)) {
                $service->uuid = (string) Str::uuid();
            }
        });
    }
}
