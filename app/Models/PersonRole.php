<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonRole extends Model
{
    protected $fillable = ['person_id', 'role', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
