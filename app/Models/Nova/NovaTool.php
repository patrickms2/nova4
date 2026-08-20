<?php
declare(strict_types=1);

namespace App\Models\Nova;

use App\Enums\Nova\NovaToolType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NovaTool extends Model
{
    use HasFactory;

    protected $fillable = ['capability_id', 'key', 'name', 'description', 'type', 'handler', 'icon', 'sort', 'settings'];

    protected function casts(): array
    {
        return ['type' => NovaToolType::class, 'sort' => 'integer', 'settings' => 'array'];
    }

    public function capability(): BelongsTo
    {
        return $this->belongsTo(NovaCapability::class, 'capability_id');
    }

    public function bindings(): HasMany
    {
        return $this->hasMany(NovaBinding::class, 'tool_id');
    }
}
