<?php
declare(strict_types=1);

namespace App\Models\Nova;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NovaGroup extends Model
{
    use HasFactory;

    protected $fillable = ['panel_id', 'parent_id', 'key', 'name', 'icon', 'sort', 'settings'];

    protected function casts(): array
    {
        return ['sort' => 'integer', 'settings' => 'array'];
    }

    public function panel(): BelongsTo
    {
        return $this->belongsTo(NovaPanel::class, 'panel_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function bindings(): HasMany
    {
        return $this->hasMany(NovaBinding::class, 'group_id')->orderBy('sort');
    }

}
