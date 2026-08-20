<?php

namespace App\Models\Taxi;

use App\Models\User;
use App\Traits\HasOwnRecord;
   
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Attendance extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceFactory> */
    use HasFactory;


    protected $table = 'usuarios_attendances';

    /**
     * The attributes that should be guarded.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];
    protected $fillable = [
        'id', 'tipo_id', 'description', 'usuario_id', 'employee_id', 'date', 'startDate', 'endDate', 'duration', 'task_id', 'project_id', 'status',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'startDate' => 'datetime:H:i',
            'endDate' => 'datetime:H:i',
            'date' => 'datetime',
            'duration' => 'int',
        ];
    }

    public function setDurationAttribute($value)
    {
        return $this->attributes['duration'] = $this->startDate->diffInMinutes($this->endDate);
    }

    public function getDurationAttribute($value)
    {
        return $this->startDate->diffInMinutes($this->endDate);
    }

    /**
     * Get the employee that owns the attendance.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Get the attendance type for this attendance.
     */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoAttendance::class);
    }

    public function getEmployeeIdAttribute(): ?int
    {
        return isset($this->attributes['usuario_id']) ? (int) $this->attributes['usuario_id'] : null;
    }

    public function setEmployeeIdAttribute(?int $value): void
    {
        $this->attributes['usuario_id'] = $value;
    }

}
