<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Builder;
use App\Enums\UsuarioTipo;
use App\Models\Taxi\Departamento;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Empleado extends Usuario
{
    use HasFactory, Notifiable, InteractsWithMedia, HasRoles;

    protected $table = 'usuarios';

    const PROFILE = 'profile';
    const ADMIN = 1;

    const CLIENT = 2;

    const LANGUAGES = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
    ];

    const LANGUAGES_IMAGE = [
        'en' => 'web/media/flags/united-states.svg',
        'es' => 'web/media/flags/spain.svg',
        'fr' => 'web/media/flags/france.svg',
        'de' => 'web/media/flags/germany.svg',
    ];

    protected $perPage = 20;
    protected $primaryKey = 'id';

    protected static function booted(): void
    {
        static::addGlobalScope('tipo_id', function (Builder $builder) {
            $builder
                ->where('tipo_id', 1)
                ->orWhere('tipo_id', 3);
        });
    }

    public function newQuery($excludeDeleted = true): Builder
    {
        return parent::newQuery($excludeDeleted)->where('tipo_id', 1)->orWhere('tipo_id', 3);;
    }

    /**
     * Método para obtener solo datos esenciales del taxista para listados
     * Mejora el rendimiento al cargar solo campos necesarios
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeParaListado($query)
    {
        return $query->select([
            'id', 'nombre', 'email', 'tel_fijo', 'estado_id', 'municipio_id', 'lat', 'lng', 'licencia'
        ])->with([
            'municipio:id,nombre',
            'estado:id,nombre'
        ]);
    }

    public function direcciones(): HasMany
    {
        return $this->hasMany(UsuarioDireccion::class, 'usuario_id');
    }

    /**
     * Método para obtener datos completos del taxista con sus relaciones principales
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeConDatosCompletos($query)
    {
        return $query->with([
            'municipio:id,nombre',
            'estado:id,nombre',
            'departamento:id,nombre',
            'taxis' => function ($query) {
                $query->select(['id', 'matricula', 'modelo', 'taxista_id', 'tipostaxi_id'])
                    ->with('tipotaxi:id,nombre');
            },
            'documentos' => function ($query) {
                $query->select(['id', 'user_id', 'file_name', 'tipo_id', 'favorito'])
                    ->with('tipo:id,nombre');
            }
        ]);
    }

    /**
     * Obtiene el valor de la clave primaria del modelo.
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /*protected function casts(): array
    {
        return [
            'status' => UsuarioTipo::class
        ];
    }*/
    /**
     * Obtiene el título que se mostrará en la tarjeta Kanban
     *
     * @return string
     */
    public function getKanbanRecordTitleAttribute(): string
    {
        return $this->nombre;
    }

    /**
     * Obtiene la descripción que se mostrará en la tarjeta Kanban
     *
     * @return string|null
     */
    public function getKanbanRecordDescriptionAttribute(): ?string
    {
        return $this->email;
    }

    protected $appends = [
        'location', 'status', 'latlng', 'is_encargado',
    ];


    protected $fillable = [
        'nombre', 'cif', 'tel_fijo', 'fax', 'email', 'direccion', 'provincia', 'poblacion', 'codpostal',
        'municipio_id', 'logo', 'foto', 'tipo_id', 'estado_id', 'usuario', 'password', 'fecha_baja', 'fecha_alta',
        'conectado', 'last_login', 'connected', 'bloqueado', 'licencia', 'departamento_id', 'status', 'status_id',
    ];

    // Relaciones
    public function tipo()
    {
        return $this->belongsTo(TipoUsuario::class, 'tipo_id');
    }

    public function estado()
    {
        return $this->belongsTo(EstadosUsuario::class, 'estado_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'file_name', 'tipo_id', 'departamento_id', 'favorito'])
            ->with(['tipo:id,nombre', 'departamento:id,nombre']);
    }

    public function taxis(): HasMany
    {
        return $this->hasMany(Taxi::class, 'taxista_id')
            ->select(['id', 'matricula', 'modelo', 'taxista_id', 'tipostaxi_id', 'licencia', 'estado'])
            ->with('tipotaxi:id,nombre');
    }

    public function turnos(): BelongsToMany
    {
        return $this->belongsToMany(Turnos::class, 'usuarios_turnos', 'usuario_id', 'turno_id')
            ->select(['turnos.id', 'name', 'start_time', 'end_time'])
            ->withPivot(['start_date', 'end_date'])
            ->withTimestamps();
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'usuario_id')
            ->select(['id', 'nombre', 'usuario_id', 'estado_id', 'fecha_servicio', 'tipotaxi_id', 'municipio_id'])
            ->with(['estado:id,nombre', 'tipotaxi:id,nombre']);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'nombre', 'importe', 'estado_id', 'fecha_servicio']);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'departamento_id', 'name', 'status', 'priority'])
            ->with('departamento:id,nombre');
    }

    // Relación pdfs es redundante ya que existe documentos que apunta a la misma tabla

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'departamento_id', 'appointment_date', 'appointment_time', 'status'])
            ->with('departamento:id,nombre');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'date', 'startDate', 'endDate']);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'start_date', 'end_date', 'status']);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'name', 'price', 'status']);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'fecha', 'total', 'estado']);
    }

    public function getIsEncargadoAttribute(): bool
    {
        $usuario = $this->id;
        $total = Departamento::query()
            ->where("usuario_id", $usuario)
            ->count();

        return (bool)$total > 0 ? 1 : 0;
    }

    public function getStatusAttribute(): bool
    {
        return (bool)$this->estado_id ? 1 : 0;
    }

    public function getLocationAttribute(): array
    {
        return [
            "lat" => (float)$this->lat,
            "lng" => (float)$this->lng,
        ];
    }

    public function setLocationAttribute(?array $location): void
    {
        if (is_array($location)) {
            $this->attributes['lat'] = $location['lat'];
            $this->attributes['lng'] = $location['lng'];
            unset($this->attributes['location']);
        }
    }

    /**
     * Obtener los atributos de latitud y longitud
     * @return array
     */

    public function getLatLngAttribute(): string
    {
        return "(" . (string)$this->lat . "," . (string)$this->lng . ")";
    }

    public static function getComputedLocation(): string
    {
        return 'location';
    }

    /**
     * Método estático para obtener nombres de atributos de latitud y longitud
     * @return array
     */
    public static function getLatLngAttributeNames(): array
    {
        return [
            "lat" => "lat",
            "lng" => "lng",
        ];
    }

    public function scopeDepartamento($query)
    {
        return $query->where('tipo_id', 5);
    }

    public function scopeEmpleado($query)
    {
        return $query->where('tipo_id', 1);
    }

    /**
     * Scope para filtrar taxistas destacados
     */
    public function scopeEncargado($query)
    {
        return $query->where('is_encargado', true);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeByStatus($query, $estadoId)
    {
        return $query->where('estado_id', $estadoId);
    }

    /**
     * Scope para filtrar por localidad
     */
    public function scopeLocale($query, $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope para filtrar taxistas activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado_id', 1);
    }

    /**
     * Scope para filtrar taxistas por municipio
     */
    public function scopePorMunicipio($query, $municipioId)
    {
        return $query->where('municipio_id', $municipioId);
    }

    /**
     * Scope para ordenar por nombre
     */
    public function scopeOrdenadoPorNombre($query, $direccion = 'asc')
    {
        return $query->orderBy('nombre', $direccion);
    }

    /**
     * Scope para ordenar por última conexión
     */
    public function scopeOrdenadoPorUltimaConexion($query, $direccion = 'desc')
    {
        return $query->orderBy('last_login', $direccion);
    }


}
