<?php
declare(strict_types=1);

namespace App\Models\Nova;

use App\Enums\Nova\NovaBindingTarget;
use App\Enums\Nova\NovaRepresentationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NovaBinding extends Model
{
    use HasFactory;

    protected $fillable = [
        'panel_id', 'group_id', 'capability_id', 'tool_id', 'resource_id',
        'relation_id', 'connector_id', 'target_type', 'role',
        'representation', 'visible', 'sort', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'target_type' => NovaBindingTarget::class,
            'representation' => NovaRepresentationType::class,
            'visible' => 'boolean',
            'sort' => 'integer',
            'settings' => 'array',
        ];
    }

    public function panel(): BelongsTo { return $this->belongsTo(NovaPanel::class, 'panel_id'); }
    public function group(): BelongsTo { return $this->belongsTo(NovaGroup::class, 'group_id'); }
    public function capability(): BelongsTo { return $this->belongsTo(NovaCapability::class, 'capability_id'); }
    public function tool(): BelongsTo { return $this->belongsTo(NovaTool::class, 'tool_id'); }
    public function resource(): BelongsTo { return $this->belongsTo(NovaResource::class, 'resource_id'); }
    public function relation(): BelongsTo { return $this->belongsTo(NovaRelation::class, 'relation_id'); }
    public function connector(): BelongsTo { return $this->belongsTo(NovaConnector::class, 'connector_id'); }
}
