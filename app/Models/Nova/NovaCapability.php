<?php
declare(strict_types=1);

namespace App\Models\Nova;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NovaCapability extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'name', 'description', 'icon', 'status', 'settings'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function tools(): HasMany
    {
        return $this->hasMany(NovaTool::class, 'capability_id')->orderBy('sort');
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(NovaResource::class, 'nova_capability_resource', 'capability_id', 'resource_id')
            ->withPivot(['role', 'sort', 'settings'])
            ->withTimestamps();
    }

    public function connectors(): BelongsToMany
    {
        return $this->belongsToMany(NovaConnector::class, 'nova_capability_connector', 'capability_id', 'connector_id')
            ->withPivot(['direction', 'sort', 'settings'])
            ->withTimestamps();
    }

    public function bindings(): HasMany
    {
        return $this->hasMany(NovaBinding::class, 'capability_id');
    }

}
