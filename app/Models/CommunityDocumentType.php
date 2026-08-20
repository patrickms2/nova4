<?php

namespace App\Models;

use Database\Factories\CommunityDocumentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityDocumentType extends Model
{
    /** @use HasFactory<CommunityDocumentTypeFactory> */
    use HasFactory;

    protected $fillable = ['community_id', 'name', 'code', 'description', 'requires_expiration', 'is_active'];

    protected function casts(): array
    {
        return ['requires_expiration' => 'boolean', 'is_active' => 'boolean'];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CommunityOwnerDocument::class);
    }
}
