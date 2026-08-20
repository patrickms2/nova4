<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;

class DepartamentoUsuario extends Model
{

	protected $table = 'departamentos_usuarios';
	protected $fillable = ["departamento_id","usuario_id"];


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
