<?php

namespace App\Models\Taxi;

use App\Models\Taxi\Message;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Topic extends Model
{
    protected $fillable = [
        'subject',
        'creator_id',
        'receiver_id',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'topic_id')
            ->orderBy('created_at', 'desc');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'creator_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'receiver_id')->where('id', '!=', auth()->id());
    }

    public function markMessagesAsRead(): void
    {
        foreach ($this->messages() as $message) {
            if ($message->sender_id !== auth()->id() && is_null($message->read_at)) {
                $message->read_at = now();
                $message->save();
            }
        }
    }
}
