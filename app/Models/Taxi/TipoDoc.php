<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Taxi\UsuarioTipodoc;

class TipoDoc extends Model
{

    // Constantes para tipos de acceso
    const ACCESO_PRIVADO = 'privado';
    const ACCESO_DEPARTAMENTO = 'departamento';
    const ACCESO_AMBOS = 'ambos';
    const ACCESO_PUBLICO = 'publico';
    const ACCESO_USUARIOS = 'usuarios';


    protected $table = 'tipos_docs';

    /**
     * Campos que pueden ser asignados en masa.
     *
     * @var array
     */
    protected $fillable = ['id', 'nombre', 'color', 'estado', 'favorito', 'tipo_acceso', 'slug', 'icono', 'status', 'order'];

    /**
     * Clave primaria de la tabla.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Atributos virtuales para el formulario.
     *
     * @var array
     */
    protected $appends = [];
    protected $casts = [
        'asignado_a_departamentos' => 'boolean',
        'asignado_a_usuarios' => 'boolean',
    ];


    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from title
        static::creating(function ($tipodoc) {
            if (empty($tipodoc->slug)) {
                $tipodoc->slug = Str::slug($tipodoc->nombre);
            }
        });

        static::updating(function ($tipodoc) {
            if ($tipodoc->isDirty('nombre') && !$tipodoc->isDirty('slug')) {
                $tipodoc->slug = Str::slug($tipodoc->nombre);
            }
        });
    }

    public function initials(): ?string
    {
        return Str::substr($this->nombre, 0, 2);
    }

    /**
     * Relación con el modelo `Documento`. Un tipo de documento puede tener muchos PDFs.
     *
     * @return HasMany
     */
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'tipo_id');
    }

    /**
     * Relación con el modelo `Usuario`. Un tipo de documento puede estar asociado a muchos usuarios.
     *
     * @return HasMany
     */
    public function empleados()
    {
        return $this->hasMany(Usuario::class, 'tipo_id');
    }

}
