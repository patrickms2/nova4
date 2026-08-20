<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkReport extends Model
{
    /** @use HasFactory<\Database\Factories\WorkReportFactory> */
    use HasFactory;

    protected $fillable = [
        'work_session_id',
        'voice_path',
        'voice_transcription',
        'summary',
        'photos',
        'ai_metadata',
        'submitted_at',
    ];

    protected $casts = [
        'photos' => 'array',
        'ai_metadata' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }

    public function photoCount(): int
    {
        return count($this->photos ?? []);
    }
}
