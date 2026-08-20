<?php
declare(strict_types=1);

namespace App\Models\Nova;

use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NovaWorkspace extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'name', 'description', 'status', 'settings'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function panels(): HasMany
    {
        return $this->hasMany(NovaPanel::class, 'workspace_id');
    }

}
