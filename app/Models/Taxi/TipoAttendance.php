<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;

class TipoAttendance extends Model
{
    protected $table = 'tipos_attendances';

    protected $fillable = [
        'nombre',
    ];

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

}
