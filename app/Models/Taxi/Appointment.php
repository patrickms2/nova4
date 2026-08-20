<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function serviciocita()
    {
        return $this->belongsTo(ServicioCita::class);
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
