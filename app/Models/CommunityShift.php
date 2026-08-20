<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityShift extends Model
{
    use HasFactory;

    protected $fillable = ['community_id', 'community_department_id', 'employee_id', 'work_order_id', 'shift_date', 'starts_at', 'ends_at', 'status', 'notes'];

    protected function casts(): array
    {
        return ['shift_date' => 'date'];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(CommunityDepartment::class, 'community_department_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CommunityAttendance::class);
    }
}
