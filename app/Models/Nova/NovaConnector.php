<?php
declare(strict_types=1);

namespace App\Models\Nova;

use App\Enums\Nova\NovaConnectorDirection;
use App\Enums\Nova\NovaConnectorType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class NovaConnector extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'name', 'description', 'type', 'direction', 'adapter',
        'endpoint', 'status', 'credentials_key', 'settings',
    ];

    protected $hidden = ['credentials_key'];

    protected function casts(): array
    {
        return [
            'type' => NovaConnectorType::class,
            'direction' => NovaConnectorDirection::class,
            'settings' => 'array',
        ];
    }

    public function capabilities(): BelongsToMany
    {
        return $this->belongsToMany(NovaCapability::class, 'nova_capability_connector', 'connector_id', 'capability_id')
            ->withPivot(['direction', 'sort', 'settings'])
            ->withTimestamps();
    }
}
