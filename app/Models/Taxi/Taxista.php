<?php

namespace App\Models\Taxi;

use App\Models\Announcement;
use App\Models\Taxi\Conductor;
use App\Models\Taxi\Taxi;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\UsuarioTipo;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Taxi\TaxistaConductor;
use App\Models\Taxi\TaxistaTaxi;
use App\Models\Taxi\Servicio;
use App\Models\Taxi\Pago;
use App\Models\Taxi\Ticket;
use App\Models\Taxi\Cita;
use App\Models\Taxi\Attendance;
use App\Models\Taxi\Invoice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Taxista extends Usuario implements HasMedia
{
    use HasFactory, Notifiable, InteractsWithMedia;

    protected $table = 'usuarios';
    const COLLECTION_PROFILE_PICTURES = 'profile_photo';
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
            $builder->where('tipo_id', 4);
        });
    }

    public function newQuery($excludeDeleted = true): Builder
    {
        return parent::newQuery($excludeDeleted)->where('tipo_id', 4);
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
            'municipio:id,nombre,color',
            'estado:id,nombre',
            'taxis:id,matricula,modelo,usuario_id,tipostaxi_id,licencia,estado',
            'conductores:id,nombre,taxista_id',

        ]);
    }

    public function unreadAnnouncements(): Builder
    {
        return Announcement::query()
            ->forUsers()
            ->active()
            ->where('user_id', '!=', $this->id)
            ->whereDoesntHave('dismissedByUsers', function ($q) {
                $q->where('dismissable_id', $this->id)
                    ->where('dismissable_type', static::class);
            })
            ->orderBy('starts_at');
    }

    public function nextUnreadAnnouncement()
    {
        return $this->unreadAnnouncements()->first();
    }

    public function markAnnouncementAsRead(Announcement $announcement): void
    {
        if (!$announcement->for_users) {
            return;
        }

        $this->dismissedAnnouncements()->syncWithoutDetaching([
            $announcement->getKey() => ['read_at' => now()],
        ]);
    }

    public function scopeRecord($query)
    {
        return $query->select([
            'id', 'nombre', 'email', 'tel_fijo', 'estado_id', 'departamento_id', 'municipio_id', 'licencia'
        ])->with([
            'municipio:id,nombre,color',
            'estado:id,nombre',
            'departamento:id,nombre',
            'citas' => function ($query) {
                $query->select(['id', 'usuario_id', 'departamento_id', 'appointment_date', 'appointment_time', 'tipo_id', 'status'])
                    ->with('departamento:id,nombre');
            },
            'documentos' => function ($query) {
                $query->select(['id', 'usuario_id', 'file_name', 'tipo_id', 'attachments', 'attachment_file_names', 'departamento_id', 'favorito'])
                    ->with('tipo:id,nombre');
            },
            'tickets' => function ($query) {
                $query->select(['id', 'usuario_id', 'departamento_id', 'name', 'status', 'priority'])
                    ->with('departamento:id,nombre');
            }
        ]);
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
            'municipio:id,nombre,color',
            'estado:id,nombre',
            'departamento:id,nombre',
            'taxis' => function ($query) {
                $query->select(['id', 'matricula', 'modelo', 'usuario_id', 'tipostaxi_id'])
                    ->with('tipotaxi:id,nombre');
            },
            'conductores' => function ($query) {
                $query->select(['id', 'nombre', 'taxista_id'])
                    ->with('conductores:id,nombre');
            },
            'documentos' => function ($query) {
                $query->select(['id', 'usuario_id', 'file_name', 'tipo_id', 'favorito'])
                    ->with('tipo:id,nombre');
            }
        ]);
    }

    public function getApiImageUrlAttribute()
    {
        $media = $this->getMedia(self::COLLECTION_PROFILE_PICTURES)->first();
        if (!empty($media)) {
            return $media->getFullUrl();
        }

        return getApiUserImageInitial($this->id, $this->nombre);
    }

    public function initials(): ?string
    {
        return $this->nombre
            ? Str::of($this->nombre)
                ->explode(' ')
                ->take(2)
                ->map(fn($word) => Str::substr($word, 0, 1))
                ->implode('')
            : null;
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

    protected $fillable = [
        'nombre', 'cif', 'tel_fijo', 'fax', 'email', 'direccion', 'provincia', 'poblacion', 'codpostal',
        'municipio_id', 'logo', 'foto', 'tipo_id', 'estado_id', 'usuario', 'password', 'fecha_baja', 'fecha_alta',
        'conectado', 'last_login', 'connected', 'bloqueado', 'licencia', 'departamento_id', 'status', 'status_id',
    ];

    /** @return MorphToMany<Device, $this> */
    public function devices(): MorphToMany
    {
        return $this->morphToMany(Device::class, 'devices');
    }

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

    /** @return MorphToMany<Address, $this> */
    public function addresses(): MorphToMany
    {
        return $this->morphToMany(Address::class, 'addressable');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'usuario_id')
            ->select(['id', 'conductor_id', 'usuario_id', 'file_name', 'tipo_id', 'attachments', 'attachment_file_names', 'departamento_id', 'favorito'])
            ->with(['tipo:id,nombre', 'departamento:id,nombre,color']);
    }

    public function conductor()
    {
        return $this->belongsTo(Conductor::class, 'taxista_id');
    }

    public function conductores(): BelongsToMany
    {
        return $this->belongsToMany(Conductor::class, 'taxis_conductores', 'taxista_id', 'conductor_id');
    }

    public function conductorTaxista()
    {
        return $this->belongsToMany(Conductor::class, 'taxis_conductores', 'taxista_id', 'conductor_id');
    }

    public function taxistaConductor()
    {
        return $this->hasMany(TaxistaConductor::class, 'taxista_id');
    }

    public function taxis(): HasMany
    {
        return $this->hasMany(Taxi::class, 'usuario_id');
    }

    public function taxistaTaxi()
    {
        return $this->hasMany(TaxistaTaxi::class, 'taxista_id');
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'taxista_id');
    }

    public function dispositivos()
    {
        return $this->hasMany(Device::class, 'taxista_id');
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

    public function citas()
    {
        return $this->hasMany(Cita::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'departamento_id', 'appointment_date', 'appointment_time', 'status'])
            ->with('departamento:id,nombre');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'fecha', 'hora_entrada', 'hora_salida']);
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

    public function invoice()
    {
        return $this->hasMany(Invoice::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'fecha', 'total', 'estado']);
    }


    /**
     * Scope para filtrar taxistas destacados
     */
    public function scopeDestacado($query)
    {
        return $query->where('is_destacado', true);
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

    /**
     * Obtener objeto Municipio para el formato de respuesta
     */
    public function getMunicipioObjectAttribute()
    {
        return [
            'codmunicipio' => $this->municipio_id,
            'nombreMunicipio' => $this->nombre_municipio
        ];
    }

}
