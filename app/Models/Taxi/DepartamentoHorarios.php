<?php

namespace App\Models\Taxi;

use App\Enums\Semana;
use App\Enums\WeekDay;
use App\Models\Taxi\Departamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartamentoHorarios extends Model
{
    /** @use HasFactory<\Database\Factories\DepartamentoHorarioFactory> */
    use HasFactory;
    protected $table = 'departamentos_horarios';

    protected $dates = ['open', 'close'];
    protected $with = ['openingTimes'];
    protected $hidden = ['id','created_at', 'updated_at'];
    public $incrementing = true;
    protected $fillable = [
      'usuario_id', 'departamento_id', 'days','open', 'close','active','horarios','duration',
    ];
    public $guarded = [
        'id'
    ];
    protected $casts = [
        'open' => 'datetime:H:i',
        'close' => 'datetime:H:i',
        'days' => 'array',
        'horarios' => 'json',

    ];
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
        return $this->hasMany(OpeningTime::class, 'departamento_id');
    }
}
