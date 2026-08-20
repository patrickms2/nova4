<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class UsuarioTipodoc extends Model
{
    use HasTags;

	protected $table = 'usuarios_tiposdocs';
	protected $fillable = ["usuarios_id","tipo_id"];


	/**
     * The table primary key field
     *
     * @var string
     */


    public function tipodoc()
    {
        return $this->belongsTo(TipoDoc::class, 'tipo_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

}
