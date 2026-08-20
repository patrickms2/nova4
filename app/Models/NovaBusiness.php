<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class NovaBusiness extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'business_type',
        'status',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website_url',
        'subscription_amount',
        'commission_rate',
        'settings',
        'nova_business_id',
        'nova_service_id',
    ];

    protected $casts = [
        'settings' => 'array',
        'subscription_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public function mcpServers(): HasMany
    {
        return $this->hasMany(Server::class, 'nova_business_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(NovaBusiness::class, 'nova_business_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(NovaService::class, 'nova_business_id');
    }

    public function whatsappChannels(): HasMany
    {
        return $this->hasMany(NovaWhatsappChannel::class, 'nova_business_id');
    }

    public function aiProfiles(): HasMany
    {
        return $this->hasMany(NovaAiProfile::class, 'nova_business_id');
    }

    public function aiKnowledge(): HasMany
    {
        return $this->hasMany(NovaAiKnowledge::class, 'nova_business_id');
    }

    public function tools(): HasManyThrough
    {
        return $this->hasManyThrough(Tool::class, Server::class, 'nova_business_id', 'server_id');
    }

    public function listingCategories(): HasMany
    {
        return $this->hasMany(NovaListingCategory::class, 'nova_business_id');
    }

    public function crossSellingRules(): HasMany
    {
        return $this->hasMany(NovaCrossSellingRule::class, 'from_business_id');
    }

    public function intentRules(): HasMany
    {
        return $this->hasMany(NovaIntentRule::class, 'nova_business_id');
    }

    public function externalBookings(): HasMany
    {
        return $this->hasMany(NovaExternalBooking::class, 'nova_business_id');
    }

    public function externalCatalogItems(): HasMany
    {
        return $this->hasMany(NovaExternalCatalogItem::class, 'nova_business_id');
    }

    public function integrationSettings(): HasMany
    {
        return $this->hasMany(NovaIntegrationSetting::class, 'nova_business_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'email', 'contact_email');
    }

    public function facturas(): HasManyThrough
    {
        return $this->hasManyThrough(
            Factura::class,
            Cliente::class,
            'email',
            'cliente_id',
            'contact_email',
            'codcliente',
        );
    }
}
