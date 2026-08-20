<?php

declare(strict_types=1);

namespace App\Models\Nova;

use App\Enums\Nova\NovaRepresentationStatus;
use App\Enums\Nova\NovaRepresentationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NovaRepresentation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'panel_id',
        'resource_id',
        'capability_id',
        'type',
        'status',
        'key',
        'name',
        'class_name',
        'model_class',
        'navigation_group',
        'navigation_label',
        'navigation_icon',
        'navigation_sort',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'type' => NovaRepresentationType::class,
            'status' => NovaRepresentationStatus::class,
            'navigation_sort' => 'integer',
            'settings' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(NovaWorkspace::class, 'workspace_id');
    }

    public function panel(): BelongsTo
    {
        return $this->belongsTo(NovaPanel::class, 'panel_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(NovaResource::class, 'resource_id');
    }

    public function capability(): BelongsTo
    {
        return $this->belongsTo(NovaCapability::class, 'capability_id');
    }

    public function presentationNodes(): HasMany
    {
        return $this->hasMany(NovaPresentationNode::class, 'representation_id')->orderBy('sort');
    }
}
