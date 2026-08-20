<?php

namespace App\Models;

use Database\Factories\CommunityDocumentImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityDocumentImport extends Model
{
    /** @use HasFactory<CommunityDocumentImportFactory> */
    use HasFactory;

    protected $fillable = ['community_id', 'community_document_type_id', 'original_name', 'source_path', 'status', 'files_found', 'documents_created', 'unmatched_files', 'issues', 'created_by', 'processed_at'];

    protected function casts(): array
    {
        return ['issues' => 'array', 'processed_at' => 'datetime'];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(CommunityDocumentType::class, 'community_document_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
