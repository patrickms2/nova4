<?php

namespace App\Models\Taxi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'topic_id',
        'sender_id',
        'content',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'timestamp',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function sender(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'sender_id');
    }
}
