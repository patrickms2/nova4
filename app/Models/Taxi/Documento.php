<?php

declare(strict_types=1);
// app/Models/Documento.php

namespace App\Models\Taxi;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Documento extends Model
{
    public const ACCESO_PRIVADO = 'privado';

    public const ACCESO_DEPARTAMENTO = 'departamento';

    public const ACCESO_AMBOS = 'ambos';

    public const ACCESO_PUBLICO = 'publico';

    public const ACCESO_USUARIOS = 'usuarios';

    protected $table = 'usuarios_documentos';

    protected $primaryKey = 'id';

    protected $recordKey = 'id';

    protected $fillable = [
        'notas', 'usuario_id', 'conductor_id', 'file_name', 'file_path', 'referencia', 'datos', 'tipodoc', 'nif', 'year', 'mes', 'tipo_id', 'usuario_tipo_id', 'departamento_id', 'favorito', 'order', 'attachments', 'validado', 'estado', 'observaciones', 'tipo_acceso', 'attachment_file_names',
    ];

    protected $appends = ['asignado_a_departamentos', 'asignado_a_usuarios'];

    protected $casts = [
        'usuario_id' => 'integer',
        'conductor_id' => 'integer',
        'tipo_id' => 'integer',
        'usuario_tipo_id' => 'integer',
        'departamento_id' => 'integer',
        'favorito' => 'boolean',
        'created_at' => 'datetime',
        'year' => 'integer',
        'attachments' => 'array',
        'attachment_file_names' => 'array',
        'asignado_a_departamentos' => 'boolean',
        'asignado_a_usuarios' => 'boolean',
    ];

    protected $with = ['tipo'];

    public function tipo()
    {
        return $this->belongsTo(TipoDoc::class, 'tipo_id')
            ->select(['id', 'nombre', 'color', 'icono', 'slug', 'descripcion']);
    }

    public function scopeSearch($query, $value)
    {
        // Si el valor de búsqueda está vacío, retornar la consulta sin modificar
        if (empty($value)) {
            return $query;
        }

        // Usar un valor formateado para la búsqueda y evitar inyección SQL
        $searchTerm = '%' . mb_trim($value) . '%';

        return $query->where(function ($query) use ($searchTerm) {
            $query->where('observaciones', 'like', $searchTerm)
                ->orWhereHas('tipo', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', $searchTerm);
                })
                ->orWhereHas('departamento', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', $searchTerm);
                })
                ->orWhereHas('usuario', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', $searchTerm);
                })
                ->orWhereHas('conductor', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', $searchTerm);
                })
                ->orWhere('referencia', 'like', $searchTerm)
                ->orWhere('datos', 'like', $searchTerm)
                ->orWhere('nif', 'like', $searchTerm)
                ->orWhere('year', 'like', $searchTerm)
                ->orWhere('mes', 'like', $searchTerm)
                ->orWhere('tipo', 'like', $searchTerm)
                ->orWhereHas('departamento.usuario', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', $searchTerm);
                })
                ->orWhereHas('usuario', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', $searchTerm);
                });
        });
    }

    /**
     * Determina si el tipo de documento está asignado a departamentos.
     */
    public function getAsignadoADepartamentosAttribute(): bool
    {
        return $this->departamentosDocumentos()->count() > 0;
    }

    /**
     * Determina si el tipo de documento está asignado a usuarios.
     */
    public function getAsignadoAUsuariosAttribute(): bool
    {
        return $this->usuariosDocumentos()->count() > 0;
    }

    public function setAsignadoAUsuariosAttribute(): bool
    {
        return $this->usuariosDocumentos()->count() > 0;
    }

    /**
     * Relación con el modelo `Documento`. Un tipo de documento puede tener muchos PDFs.
     *
     * @return HasMany
     */
    public function documentos()
    {
        return $this->hasMany(self::class, 'tipo_id');
    }

    /**
     * Determina si el tipo de documento es privado (solo para usuarios)
     */
    public function esPrivado(): bool
    {
        return $this->tipo_acceso === self::ACCESO_PRIVADO;
    }

    /**
     * Determina si el tipo de documento es de departamento
     */
    public function esDepartamento(): bool
    {
        return $this->tipo_acceso === self::ACCESO_DEPARTAMENTO;
    }

    /**
     * Determina si el tipo de documento es de ambos tipos
     */
    public function esAmbos(): bool
    {
        return $this->tipo_acceso === self::ACCESO_AMBOS;
    }

    /**
     * Determina si el tipo de documento es de ambos tipos
     */
    public function esPublico(): bool
    {
        return $this->tipo_acceso === self::ACCESO_PUBLICO;
    }

    /**
     * Determina si el tipo de documento es de ambos tipos
     */
    public function esUsuarios(): bool
    {
        return $this->tipo_acceso === self::ACCESO_USUARIOS;
    }

    /**
     * Relación con usuarios.
     *
     * @return BelongsToMany
     */
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'usuarios_documentos_usuarios', 'documento_id', 'usuario_id');
    }

    /**
     * Relación con la tabla intermedia usuarios_tiposdocs
     *
     * @return HasMany
     */
    public function usuariosDocumentos()
    {
        return $this->hasMany(UsuariosDocumento::class, 'documento_id');
    }

    /**
     * Relación con el modelo `DepartamentoTipodoc`. Un tipo de documento puede tener muchas asociaciones
     * con departamentos a través de esta relación.
     *
     * @return HasMany
     */
    public function departamentosDocumentos()
    {
        return $this->hasMany(DepartamentoDocumento::class, 'documento_id');
    }

    /**
     * Relación con departamentos a través de DepartamentoTipodoc.
     *
     * @return BelongsToMany
     */
    public function departamentos()
    {
        return $this->belongsToMany(Departamento::class, 'departamentos_documentos', 'documento_id', 'departamento_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id')
            ->select(['id', 'nombre', 'color']);
    }

    public function usuariotipo()
    {
        return $this->belongsTo(TipoUsuario::class, 'id')
            ->select(['id', 'nombre']);
    }

    public function conductor()
    {
        return $this->belongsTo(Conductor::class, 'conductor_id')
            ->select(['id', 'nombre', 'tipo_id']);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id')
            ->select(['id', 'nombre', 'tipo_id']);
    }

    public function taxista()
    {
        return $this->belongsTo(Taxista::class, 'usuario_id')
            ->select(['id', 'nombre', 'email']);
    }

    public function tipousuario()
    {
        return $this->belongsTo(TipoUsuario::class, 'usuario_tipo_id')
            ->select(['id', 'nombre', 'color']);
    }

    public function scopeParaSemana($query, $days = 7)
    {
        return $query->where('created_at', '>=', now())
            ->where('created_at', '<=', now()->addDays($days))
            ->orderBy('created_at');
    }

    public function scopeParaHoy($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopePorDepartamento($query, $departamentoId)
    {
        return $query->where('departamento_id', $departamentoId);
    }

    public function scopePorTipo($query, $tipoId)
    {
        return $query->where('tipo_id', $tipoId);
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function getFechaAttribute()
    {
        return $this->created_at->format('d/m/Y');
    }

    public function getTituloAttribute(): string
    {
        $fin = Carbon::parse($this->created_at);
        $fin = $fin->getTranslatedShortMonthName();
        $tipo = $this->tipodoc;

        return $this->id . ' ' . mb_strtoupper($this->tipodoc) . ' ' . $fin;

    }

    public function getNombreAttribute(): string
    {
        $fin = Carbon::parse($this->created_at);
        $fin = $fin->format('Y-m-d');

        return $this->id . '-' . $fin;
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
