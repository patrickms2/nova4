<?php

declare(strict_types=1);

namespace App\Models\Nova;

use App\Enums\Nova\NovaPresentationNodeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NovaPresentationNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'representation_id',
        'parent_id',
        'node_type',
        'capability_id',
        'relation_id',
        'resource_id',
        'key',
        'label',
        'icon',
        'sort',
        'visible',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'node_type' => NovaPresentationNodeType::class,
            'sort' => 'integer',
            'visible' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function representation(): BelongsTo
    {
        return $this->belongsTo(NovaRepresentation::class, 'representation_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function capability(): BelongsTo
    {
        return $this->belongsTo(NovaCapability::class, 'capability_id');
    }

    public function relation(): BelongsTo
    {
        return $this->belongsTo(NovaRelation::class, 'relation_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(NovaResource::class, 'resource_id');
    }
}
