<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class UsuariosDocumento extends Model
{
    use HasTags;

	protected $table = 'usuarios_documentos_usuarios';
	protected $fillable = ["usuario_id","documento_id"];


	/**
     * The table primary key field
     *
     * @var string
     */


    public function documento()
    {
        return $this->belongsTo(Documento::class, 'documento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

}
