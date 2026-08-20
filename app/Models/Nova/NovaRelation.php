<?php
declare(strict_types=1);

namespace App\Models\Nova;

use App\Enums\Nova\NovaRelationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NovaRelation extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'name', 'source_resource_id', 'target_resource_id', 'type',
        'relation_name', 'foreign_key', 'local_key', 'inverse_relation_name', 'settings',
    ];

    protected function casts(): array
    {
        return ['type' => NovaRelationType::class, 'settings' => 'array'];
    }

    public function sourceResource(): BelongsTo
    {
        return $this->belongsTo(NovaResource::class, 'source_resource_id');
    }

    public function targetResource(): BelongsTo
    {
        return $this->belongsTo(NovaResource::class, 'target_resource_id');
    }
}
