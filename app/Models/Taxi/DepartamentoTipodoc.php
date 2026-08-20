<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;

class DepartamentoTipodoc extends Model
{

	protected $table = 'departamentos_tiposdocs';
	protected $fillable = ["departamento_id","tipo_id"];


	/**
     * The table primary key field
     *
     * @var string
     */


    public function tipodoc()
    {
        return $this->belongsTo(TipoDoc::class, 'tipo_id');
    }
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

}
