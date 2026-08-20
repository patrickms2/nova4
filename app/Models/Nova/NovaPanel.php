<?php
declare(strict_types=1);

namespace App\Models\Nova;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NovaPanel extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'key', 'name', 'description', 'icon', 'sort', 'settings'];

    protected function casts(): array
    {
        return ['sort' => 'integer', 'settings' => 'array'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(NovaWorkspace::class, 'workspace_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(NovaGroup::class, 'panel_id')->orderBy('sort');
    }

    public function bindings(): HasMany
    {
        return $this->hasMany(NovaBinding::class, 'panel_id');
    }

}
