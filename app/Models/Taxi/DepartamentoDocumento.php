<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;

class DepartamentoDocumento extends Model
{

	protected $table = 'departamentos_documentos';
	protected $fillable = ["departamento_id","documento_id"];


	/**
     * The table primary key field
     *
     * @var string
     */


    public function documento()
    {
        return $this->belongsTo(Documento::class, 'documento_id');
    }
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

}
