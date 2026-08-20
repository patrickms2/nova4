<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NovaModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'version',
        'requires',
        'status',
        'metadata',
        'installed_at',
        'activated_at',
    ];

    protected $casts = [
        'requires' => 'array',
        'metadata' => 'array',
        'installed_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeWithError($query)
    {
        return $query->where('status', 'error');
    }

    public function isInstalled(): bool
    {
        return !is_null($this->installed_at);
    }

    public function isActivated(): bool
    {
        return !is_null($this->activated_at);
    }

    public function hasErrors(): bool
    {
        return $this->status === 'error';
    }
}
