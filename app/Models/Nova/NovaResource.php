<?php
declare(strict_types=1);

namespace App\Models\Nova;

use App\Enums\Nova\NovaResourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NovaResource extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'name', 'description', 'type', 'class_name', 'source', 'settings'];

    protected function casts(): array
    {
        return ['type' => NovaResourceType::class, 'settings' => 'array'];
    }

    public function capabilities(): BelongsToMany
    {
        return $this->belongsToMany(NovaCapability::class, 'nova_capability_resource', 'resource_id', 'capability_id')
            ->withPivot(['role', 'sort', 'settings'])
            ->withTimestamps();
    }

    public function outgoingRelations(): HasMany
    {
        return $this->hasMany(NovaRelation::class, 'source_resource_id');
    }

    public function incomingRelations(): HasMany
    {
        return $this->hasMany(NovaRelation::class, 'target_resource_id');
    }
}
