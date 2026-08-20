<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;

class UsuarioDepartamento extends Model
{

	protected $table = 'usuarios_departamentos';
	protected $fillable = ["usuario_id","departamento_id"];


	/**
     * The table primary key field
     *
     * @var string
     */


    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

}
