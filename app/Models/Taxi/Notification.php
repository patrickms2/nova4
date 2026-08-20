<?php

namespace App\Models\Taxi;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Notification
 *
 * @property int $id
 * @property int $type
 * @property int $user_id
 * @property string $title
 * @property string|null $text
 * @property string|null $meta
 * @property string|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 *
 * @method static Builder|Notification newModelQuery()
 * @method static Builder|Notification newQuery()
 * @method static Builder|Notification query()
 * @method static Builder|Notification whereCreatedAt($value)
 * @method static Builder|Notification whereId($value)
 * @method static Builder|Notification whereMeta($value)
 * @method static Builder|Notification whereReadAt($value)
 * @method static Builder|Notification whereText($value)
 * @method static Builder|Notification whereTitle($value)
 * @method static Builder|Notification whereType($value)
 * @method static Builder|Notification whereUpdatedAt($value)
 * @method static Builder|Notification whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Notification extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    public $table = 'notifications';

    /**
     * @var string[]
     */
    public $fillable = [
        'type',
        'user_id',
        'title',
        'text',
        'meta',
        'read_at',
    ];

    protected $casts = [
        'type' => 'integer',
        'title' => 'string',
        'text' => 'string',
        'meta' => 'json',
        'read_at' => 'datetime',
        'user_id' => 'integer',
    ];

    const NOTIFICATION_TYPE = [
        'Invoice Created' => 1,
        'Invoice Updated' => 2,
        'Invoice Payment' => 3,
        'Invoice Status' => 4,
        'Quote Created' => 5,
        'Quote Updated' => 6,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
}
