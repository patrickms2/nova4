<?php

namespace App\Models;

use Database\Factories\CommunityEmployeeDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityEmployeeDocument extends Model
{
    /** @use HasFactory<CommunityEmployeeDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'community_id',
        'employee_id',
        'work_order_id',
        'title',
        'description',
        'path',
        'filename',
        'mime_type',
        'size',
        'status',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
