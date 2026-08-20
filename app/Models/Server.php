<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'nova_business_id',
        'nova_service_id',
        'name', 'slug', 'description', 'version', 'instructions',
        'transport', 'auth_type', 'credentials', 'endpoint', 'middleware', 'metadata', 'is_active',
        'status', 'capabilities', 'last_checked_at', 'last_error',
    ];

    protected $casts = [
        'middleware' => 'array',
        'metadata' => 'array',
        'credentials' => 'array',
        'capabilities' => 'array',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Server $server) {
            if (empty($server->slug)) {
                $server->slug = Str::slug($server->name);
            }
            if (empty($server->endpoint)) {
                $server->endpoint = '/mcp/'.$server->slug;
            }
        });
    }

    public function novaBusiness(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'nova_business_id');
    }

    public function novaService(): BelongsTo
    {
        return $this->belongsTo(NovaService::class, 'nova_service_id');
    }

    public function tools(): HasMany
    {
        return $this->hasMany(Tool::class,'server_id')->orderBy('sort_order');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class)->orderBy('sort_order');
    }

    public function prompts(): HasMany
    {
        return $this->hasMany(Prompt::class)->orderBy('sort_order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(McpLog::class);
    }

    public function externalSources(): HasMany
    {
        return $this->hasMany(ExternalSource::class);
    }

    public function scopeForNova($query)
    {
        return $query->whereNotNull('nova_business_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithCapability($query, string $capability)
    {
        return $query->whereJsonContains('capabilities', $capability);
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }
}
