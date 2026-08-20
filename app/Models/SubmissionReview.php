<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_submission_id',
        'review_status',
        'notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function serviceSubmission(): BelongsTo
    {
        return $this->belongsTo(ServiceSubmission::class);
    }
}
