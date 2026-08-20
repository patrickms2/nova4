<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicioCita extends Model
{
    use SoftDeletes;

    protected $table = 'departamentos_servicioscitas';

    protected $guarded = [];

    public function tipoServiciocita()
    {
        return $this->belongsTo(TipoServiciocita::class);
    }

    public function citas()
    {
        return $this->hasMany(Appointment::class);
    }

    public function departamentos()
    {
        return $this->belongsToMany(Departamento::class);
    }


}
