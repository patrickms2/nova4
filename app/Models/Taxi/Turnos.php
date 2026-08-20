<?php

namespace App\Models\Taxi;

use App\Enums\WeekDay;
   
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Turnos extends Model
{


    protected $table = 'central_turnos';

    // add fillable
    protected $fillable = [
        'name',
        'description',
        'start_time',
        'end_time',
        'weekoffs',
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'weekoffs' => 'array',
    ];

    public function setWeekoffsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['weekoffs'] = json_encode($value);
        } else {
            $this->attributes['weekoffs'] = $value;
        }
    }

    public function getWeekoffsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getWeekoffsEnumAttribute()
    {
        return collect($this->weekoffs)->map(function ($day) {
            return WeekDay::tryFrom($day);
        })->filter()->values();
    }

    /**
     * Relación con usuarios optimizada para cargar solo datos necesarios
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'usuarios_turnos', 'turno_id', 'usuario_id')
                    ->select(['usuarios.id', 'nombre', 'email', 'tipo_id'])
                    ->withPivot(['start_date', 'end_date'])
                    ->withTimestamps();
    }
    public function empleados(): BelongsToMany
    {
        return $this->belongsToMany(Empleado::class, 'usuarios_turnos', 'turno_id', 'usuario_id')
            ->select(['usuarios.id', 'nombre', 'email', 'tipo_id'])
            ->withPivot(['start_date', 'end_date'])
            ->withTimestamps();
    }
    /**
     * Relación con taxistas optimizada
     */
    public function taxistas(): BelongsToMany
    {
        return $this->belongsToMany(Taxista::class, 'usuarios_turnos', 'turno_id', 'usuario_id')
                    ->select(['usuarios.id', 'nombre', 'email'])
                    ->withPivot(['start_date', 'end_date'])
                    ->withTimestamps();
    }

    /**
     * Scope para turnos activos
     */
    public function scopeActivos($query)
    {
        return $query->whereNotNull('start_time')->whereNotNull('end_time');
    }
}
