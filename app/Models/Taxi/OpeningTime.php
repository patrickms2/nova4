<?php

namespace App\Models\Taxi;

use App\Enums\Semana;
use Illuminate\Database\Eloquent\Model;

class OpeningTime extends Model
{
    protected $table = 'departamentos_horarios';

    protected $fillable = [
        'usuario_id', 'departamento_id', 'days', 'open', 'close', 'active', 'horarios', 'duration',
    ];

    public $guarded = [
        'id',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $dates = ['open', 'close'];

    public $incrementing = true;

    protected $with = ['departamento:id,nombre,color,usuario_id'];

    protected $casts = [
        'active' => 'boolean',
        'open' => 'datetime:H:i',
        'close' => 'datetime:H:i',
        'days' => 'array',
        'horarios' => 'json',
    ];

    public function setHorariosAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['horarios'] = json_encode($value);
        } else {
            $this->attributes['horarios'] = $value;
        }
    }

    public function getHorariosAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        $horas = is_array($decoded) ? $decoded : [];

        return is_array($horas) ? $horas : [];
    }

    public function getHorariosEnumAttribute()
    {

        $horas = [];
        foreach ($this->horarios as $key => $value) {
            $horas[$key] = $key;
            $horas[$key] = [$value['open'], $value['close']];
        }

        return $horas;
    }

    public function setDaysAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['days'] = json_encode($value);
        } else {
            $this->attributes['days'] = $value;
        }
    }

    public function getDaysAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getDaysEnumAttribute()
    {
        return collect($this->days)->map(function ($day) {
            return Semana::tryFrom($day);
        })->filter()->values();
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function openingTimes()
    {
        return $this->hasMany(OpeningTime::class, 'departamento_id', 'id');
    }

    public function citas()
    {
        return $this->hasMany(Cita::class);
    }

    /**
     * Devuelve un array [id => nombre] de los departamentos que tienen al menos un horario registrado.
     */
    public static function departamentoConHorarios($usuarioId)
    {
        $ids = static::query()
            ->select('departamento_id')
            ->whereNotNull('departamento_id')
            ->distinct()
            ->pluck('departamento_id');

        return Departamento::query()
            ->porEncargado($usuarioId)
            ->whereIn('id', $ids)
            ->pluck('id');
    }

    public static function departamentosConHorarios($usuarioId): array
    {
        $ids = static::query()
            ->select('departamento_id')
            ->whereNotNull('departamento_id')
            ->distinct()
            ->pluck('departamento_id');

        return Departamento::query()
            ->whereIn('id', $ids)
            ->porEncargado($usuarioId)
            ->pluck('nombre', 'id')
            ->toArray();
    }

    /**
     * Devuelve un array [id => nombre] de los departamentos que tienen al menos un horario registrado.
     */
    public static function departamentoConHorariosT()
    {
        $ids = static::query()
            ->select('departamento_id')
            ->whereNotNull('departamento_id')
            ->distinct()
            ->pluck('departamento_id');

        return Departamento::query()
            ->whereIn('id', $ids)
            ->pluck('id');
    }

    public static function departamentosConHorariosT(): array
    {
        $ids = static::query()
            ->select('departamento_id')
            ->whereNotNull('departamento_id')
            ->distinct()
            ->pluck('departamento_id');

        return Departamento::query()
            ->whereIn('id', $ids)
            ->pluck('nombre', 'id')
            ->toArray();
    }

    public static function departamentosConHorariosColores(): array
    {
        $ids = static::query()
            ->select('departamento_id')
            ->whereNotNull('departamento_id')
            ->distinct()
            ->pluck('departamento_id');

        return Departamento::query()
            ->whereIn('id', $ids)
            ->pluck('color', 'id')
            ->toArray();
    }
}
