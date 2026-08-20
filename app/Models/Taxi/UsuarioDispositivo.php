<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class UsuarioDispositivo extends Model
{
    use HasTags;

    protected $table = 'usuarios_dispositivos';
    protected $fillable = ["usuario_id","dispositivo_id"];


    /**
     * The table primary key field
     *
     * @var string
     */


    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function dispositivo()
    {
        return $this->belongsTo(Device::class, 'dispositivo_id');
    }


}
