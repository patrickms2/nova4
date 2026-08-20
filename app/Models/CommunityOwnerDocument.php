<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityOwnerDocument extends Model
{
    use HasFactory;

    protected $fillable = ['community_id', 'person_id', 'property_id', 'community_document_type_id', 'type', 'title', 'path', 'status', 'document_date', 'expires_at', 'metadata', 'uploaded_by'];

    protected function casts(): array
    {
        return ['document_date' => 'date', 'expires_at' => 'date', 'metadata' => 'array'];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(CommunityProperty::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(CommunityDocumentType::class, 'community_document_type_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
