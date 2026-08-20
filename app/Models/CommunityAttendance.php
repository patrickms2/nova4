<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommunityAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id', 'community_department_id', 'employee_id', 'community_shift_id',
        'attendance_date', 'checked_in_at', 'check_in_latitude', 'check_in_longitude',
        'check_in_accuracy', 'checked_out_at', 'check_out_latitude', 'check_out_longitude',
        'check_out_accuracy', 'type', 'status', 'notes', 'closing_audio_path',
        'closing_audio_mime_type', 'transcription_status', 'transcription_error', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
        ];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_attendance_community')->withTimestamps();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(CommunityDepartment::class, 'community_department_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CommunityShift::class, 'community_shift_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
