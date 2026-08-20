<?php

// app/Models/TipoUsuario.php
namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use App\Models\Taxi\Usuario;
use App\Models\Taxi\Documento;
use Spatie\Permission\Models\Role as roleModal;

class TipoUsuario  extends Model
{

    protected $table = 'tipos_usuarios';

    protected $fillable = [
        'nombre', 'color', 'estado', 'tipo', 'slug', 'icon', 'order', 'status'
    ];

    // Especifica qué campo contiene el nombre del estado
    public function getKanbanStatusTitleAttribute(): string
    {
        return $this->nombre;
    }

    // Especifica qué campo contiene el color del estado
    public function getKanbanStatusColorAttribute(): string
    {
        return $this->color ?? '#3182ce'; // Color por defecto azul si no hay definido
    }

    // Especifica cómo obtener los registros para este estado
    public function getKanbanRecordsProperty()
    {
        return $this->usuarios;
    }

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'tipo_id');
    }

}
