<?php

namespace App\Models\Taxi;

use App\Enums\UsuarioTipo;
use App\Models\Taxi\Comment;

//use App\Models\Taxi\Issue;
use App\Models\Taxi\TipoUsuario;
use App\Models\Team;
use App\Traits\HasOwnRecord;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Filament\Panel\Concerns\HasTenancy;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lab404\Impersonate\Models\Impersonate;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Taxi\UsuarioDireccion;
use App\Models\Taxi\UsuarioDepartamento;
use App\Models\Taxi\Turnos;
use App\Models\Taxi\Servicio;
use App\Models\Taxi\Pago;
use App\Models\Taxi\Ticket;
use App\Models\Taxi\Documento;
use App\Models\Taxi\Cita;
use App\Models\Taxi\Appointment;
use App\Models\Taxi\Attendance;
use App\Models\Taxi\Leave;
use App\Models\Taxi\Product;
use App\Models\Taxi\Invoice;
use App\Enums\UsuarioEstado;
use App\Models\Taxi\Taxi;
use App\Models\Taxi\Municipio;
use App\Models\Taxi\Departamento;
use App\Models\Taxi\EstadosUsuario;
use App\Models\Taxi\UsuarioTipodoc;
use App\Models\User;
use App\Models\Taxi\Taxista;
use App\Models\Taxi\Hotel;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use App\Enums\Departamentos;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Usuario extends Model
{

    protected $table = 'usuarios';
    const COLLECTION_PROFILE_PICTURES = 'profile_photo';

    const PROFILE = 'profile';
    const ADMIN = 1;

    const CLIENT = 2;

    public function canImpersonate()
    {
        return true;
    }

    public function canBeImpersonated()
    {
        return true;
    }

    public function isSuperAdmin(): bool
    {
        return true;
    }


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

    public function search(Builder $query, ?string $search): void
    {
        $query->when($search, function ($q, $search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('nombre', 'like', "%{$search}%")
                    ->orWhere('tel_fijo', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

            });
        });
    }

    public function getProfileImageAttribute(): string
    {
        /** @var Media $media */
        $media = $this->getMedia(self::PROFILE)->first();
        if (!empty($media)) {
            return $media->getFullUrl();
        }

        return asset('assets/images/avatar.png');
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

    public function getRoleNameAttribute($value)
    {
        if (isset($this->tipo->nombre)) {
            return $this->tipo->nombre;
        }
        if (isset($this->tipo->name)) {
            return $this->tipo->name;
        }
        if (is_null($value)) {
            return 'Tipo';

        }
        return $value;
    }
    /*public function setRoleNameAttribute()
    {
        return $this->attributes['role_name'] = $this->tipo()->nombre;
    }*/

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

    public function getIsEncargadoAttribute(): bool
    {
        $usuario = $this->id;
        $total = Departamento::query()
            ->where("usuario_id", $usuario)
            ->count();

        return (bool)$total > 0 ? 1 : 0;
    }

    public function getTeamNameAttribute()
    {
        /*$role = $this->getTeamNameAttribute();

        if (! empty($role)) {
            return $role->display_name;
        }*/
    }

    public function getFullNameAttribute()
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
        'full_name',
        'role_name',
        'is_encargado',
        //'location','status','latlng',
    ];
    public static $cast = [
        'departamentos' => 'array',
        'turnos' => 'array',
    ];
    public static $rules = [
        'nombre' => 'required',
        'email' => 'required|email:filter|unique:users,email',
        'password' => 'required|same:password_confirmation|min:6',
    ];
    protected $fillable = [
        'nombre', 'cif', 'tel_fijo', 'fax', 'email', 'municipio_id', 'logo', 'foto', 'tipo_id', 'estado_id', 'usuario', 'password', 'fecha_baja', 'fecha_alta',
        'conectado', 'last_login', 'connected', 'bloqueado', "version",
        'licencia', 'departamento_id', 'status', 'status_id', 'user_id', 'departamentos', 'turnos'
    ];


    /**
     * return list page fields of the model.
     *
     * @return array
     */
    public static function listFields(){
        return [
            "usuarios.nombre AS nombre",
            "usuarios.tel_fijo AS tel_fijo",
            "usuarios.direccion AS direccion",
            "usuarios.provincia AS provincia",
            "usuarios.poblacion AS poblacion",
            "usuarios.codpostal AS codpostal",
            "usuarios.tipo_id AS tipo_id",
            "tipos_usuarios.nombre AS tiposusuarios_nombre",
            "usuarios.estado_id AS estado_id",
            "usuarios.fecha_baja AS fecha_baja",
            "usuarios.lat AS lat",
            "usuarios.lng AS lng",
            "usuarios.municipio_id AS municipio_id",
            "usuarios.usuario AS usuario",
            "usuarios.password AS password",
            "usuarios.id AS id",
            "usuarios.cif AS cif",
            "usuarios.email AS email",
            "usuarios.fax AS fax",
            "usuarios.movil AS movil",
            "usuarios.logo AS logo",
            "usuarios.foto AS foto",
            "usuarios.fecha_alta AS fecha_alta",
            "usuarios.conectado AS conectado",
            "usuarios.uuid AS uuid",
            "usuarios.last_login AS last_login",
            "usuarios.connected AS connected",
            "usuarios.version AS version",
            "usuarios.contador_total AS contador_total",
            "usuarios.contador_ano AS contador_ano",
            "usuarios.contador_mes AS contador_mes",
            "usuarios.contador_dia AS contador_dia",
            "usuarios.contador_sem AS contador_sem",
            "usuarios.visita AS visita",
            "usuarios.llamada AS llamada",
            "usuarios.marcador_id AS marcador_id"
        ];
    }



    /**
     * return exportList page fields of the model.
     *
     * @return array
     */
    public static function exportListFields(){
        return [
            "usuarios.nombre AS nombre",
            "usuarios.tel_fijo AS tel_fijo",
            "usuarios.direccion AS direccion",
            "usuarios.provincia AS provincia",
            "usuarios.poblacion AS poblacion",
            "usuarios.codpostal AS codpostal",
            "usuarios.tipo_id AS tipo_id",
            "tipos_usuarios.nombre AS tiposusuarios_nombre",
            "usuarios.estado_id AS estado_id",
            "usuarios.fecha_baja AS fecha_baja",
            "usuarios.lat AS lat",
            "usuarios.lng AS lng",
            "usuarios.municipio_id AS municipio_id",
            "usuarios.usuario AS usuario",
            "usuarios.password AS password",
            "usuarios.id AS id",
            "usuarios.cif AS cif",
            "usuarios.email AS email",
            "usuarios.fax AS fax",
            "usuarios.movil AS movil",
            "usuarios.logo AS logo",
            "usuarios.foto AS foto",
            "usuarios.fecha_alta AS fecha_alta",
            "usuarios.conectado AS conectado",
            "usuarios.uuid AS uuid",
            "usuarios.last_login AS last_login",
            "usuarios.connected AS connected",
            "usuarios.version AS version",
            "usuarios.contador_total AS contador_total",
            "usuarios.contador_ano AS contador_ano",
            "usuarios.contador_mes AS contador_mes",
            "usuarios.contador_dia AS contador_dia",
            "usuarios.contador_sem AS contador_sem",
            "usuarios.visita AS visita",
            "usuarios.llamada AS llamada",
            "usuarios.marcador_id AS marcador_id"
        ];
    }



    /**
     * return view page fields of the model.
     *
     * @return array
     */
    public static function viewFields(){
        return [
            "id",
            "nombre",
            "tel_fijo",
            "email",
            "direccion",
            "provincia",
            "poblacion",
            "codpostal",
            "tipo_id",
            "estado_id",
            "usuario",
            "fecha_baja",
            "fecha_alta",
            "lat",
            "lng",
            "marcador_id",
            "municipio_id"
        ];
    }



    /**
     * return exportView page fields of the model.
     *
     * @return array
     */
    public static function exportViewFields(){
        return [
            "id",
            "nombre",
            "tel_fijo",
            "email",
            "direccion",
            "provincia",
            "poblacion",
            "codpostal",
            "tipo_id",
            "estado_id",
            "usuario",
            "fecha_baja",
            "fecha_alta",
            "lat",
            "lng",
            "marcador_id",
            "municipio_id"
        ];
    }



    /**
     * return edit page fields of the model.
     *
     * @return array
     */
    public static function editFields(){
        return [
            "id",
            "nombre",
            "tel_fijo",
            "email",
            "direccion",
            "provincia",
            "poblacion",
            "codpostal",
            "movil",
            "logo",
            "foto",
            "tipo_id",
            "estado_id",
            "usuario",
            "password",
            "fecha_baja",
            "fecha_alta",
            "lat",
            "lng",
            "conectado",
            "uuid",
            "last_login",
            "connected",
            "version",
            "contador_total",
            "contador_ano",
            "contador_mes",
            "contador_dia",
            "contador_sem",
            "visita",
            "llamada",
            "marcador_id",
            "municipio_id"
        ];
    }



    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
    public function setDepartamentosAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['departamentos'] = json_encode($value);
        } else {
            $this->attributes['departamentos'] = $value;
        }
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

    public function getDepartamentosAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getDepartamentosEnumAttribute()
    {
        return collect($this->departamentos)->map(function ($departamento) {
            return Departamentos::tryFrom($departamento);
        })->filter()->values();
    }

    public function setTurnosAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['turnos'] = json_encode($value);
        } else {
            $this->attributes['turnos'] = $value;
        }
    }

    /** @return MorphToMany<Address, $this> */
    public function addresses(): MorphToMany
    {
        return $this->morphToMany(Address::class, 'addressable');
    }

    /*public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'issue_user');
    }*/

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }


    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'usuario_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withPivot('role')->withTimestamps();
    }

    public function ownedProjects(): BelongsToMany
    {
        return $this->projects()->wherePivot('role', 'owner');
    }

    public function collaboratedProjects(): BelongsToMany
    {
        return $this->projects()->wherePivot('role', 'collaborator');
    }

    /** @return MorphToMany<Device, $this> */
    public function devices(): MorphToMany
    {
        return $this->morphToMany(Device::class, 'devices');
    }

    // Relaciones
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'usuario_id');
    }

    /*    public function roles()
        {
            return $this->belongsTo(TipoUsuario::class, 'tipo_id');
        }*/
    public function tipo()
    {
        return $this->belongsTo(TipoUsuario::class, 'tipo_id');
    }

    public function tiposdocs()
    {
        return $this->hasMany(UsuarioTipodoc::class, 'usuario_id');
    }

    public function estado()
    {
        return $this->belongsTo(EstadosUsuario::class, 'estado_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'usuario_id');
    }

    public function departamentos()
    {
        return $this->hasMany(UsuarioDepartamento::class, 'usuario_id');
    }

    public function departamento()
    {
        return $this->belongsTo(UsuarioDepartamento::class, 'usuario_id');
    }

    public function direccion()
    {
        return $this->belongsTo(UsuarioDireccion::class, 'usuario_id');
    }

    public function direcciones(): HasMany
    {
        return $this->hasMany(UsuarioDireccion::class, 'usuario_id')
            ->select(['id', 'usuario_id', 'address', 'street', 'city', 'state', 'country', 'zip', 'lat', 'lng']);
    }

    /*public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }*/
    public function taxis(): HasMany
    {
        return $this->hasMany(Taxi::class, 'usuario_id');
    }


    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'usuario_id')
            ->select([
                "id",
                "nombre",
                "estado_id",
                "personas",
                "fecha_servicio",
                "fecha_terminado",
                "fecha_alta",
                "observaciones",
                "usuario_id",
                "tipotaxi_id",
                "municipio_id",
                "habitacion",
                "tarjeta_credito",
                "respuesta",
                "operador_id",
                "nombre_cliente",
                "tfno_cliente",
                "bookingId AS bookingid",
                "extras"
            ])
            ->take(10);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'usuario_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'usuario_id');
    }

    public function avatar()
    {
        return $this->singleArtifact('avatar');
    }

    // Multiple files relationship
    public function documents()
    {
        return $this->artifacts('documentos');
    }

    /*    public function documentos()
        {
            return $this->hasMany(Documento::class, 'usuario_id');
        }*/

    public function citas()
    {
        return $this->hasMany(Cita::class, 'usuario_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'usuario_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'usuario_id');
    }


    public function getNameAttribute(): string
    {
        return $this->nombre;
    }

    /**
     * Scope para filtrar taxistas destacados
     */
    public function scopeEncargado($query)
    {
        return $query->where('is_encargado', true);
    }

    public function scopeDestacado($query)
    {
        return $query->where('is_destacado', true);
    }

    public function scopeByStatus($query, UsuarioEstado $estado)
    {
        return $query->where('estado_id', $estado->value);
    }

    /**
     * Get posts by locale
     */
    public function scopeLocale($query, $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope para filtrar solo usuarios de tipo hotel
     */
    public function scopeHoteles($query)
    {
        return $query->where('tipo_id', 2);
    }

    public function scopeAdmin($query)
    {
        return $query->where('tipo_id', 3);
    }

    public function scopeOperadores($query)
    {
        return $query->where('tipo_id', 1);
    }

    public function scopeTaxistas($query)
    {
        return $query->where('tipo_id', 4);
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

    /** @return Collection<int,Team> */
    /* public function getTenants(Panel $panel): Collection
     {
         return Departamento::getColumns();
     }*/

    public function getUserPanelLink(): string
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            $role = 'admin';
        }
        if ($user->hasRole('taxista')) {
            $role = 'taxista';
        }
        if ($user->hasRole('hotel')) {
            $role = 'hotel';
        }
        if ($user->hasRole('empleado')) {
            $role = 'empleado';
        }
        if ($user->hasRole('cliente')) {
            $role = 'cliente';
        }
        if ($user->hasRole('superadmin')) {
            $role = 'superadmin';
        }
        if ($user->hasRole('administrador')) {
            $role = 'administrador';
        }

        $panelPath = match ($role) {
            'admin' => filament()->getPanel('admin')->getPath(),
            'hotel' => filament()->getPanel('hoteles')->getPath(),
            'taxista' => filament()->getPanel('taxistas')->getPath(),
            'empleado' => filament()->getPanel('empleados')->getPath(),
            'cliente' => filament()->getPanel('clientes')->getPath(),
            'superadmin' => filament()->getPanel('app')->getPath(),
            'administrador' => filament()->getPanel('administrador')->getPath(),
            default => filament()->getPanel('admin')->getPath(),
        };

        return Uri::to($panelPath);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        /*$role = Auth::user()->role()->name;*/
        //dd($role);

        /*return match ($panel->getId()) {
            'admin'          => $role === 'admin',
            'taxistas'       => $role === 'taxistas',
            'hoteles'       => $role === 'hotel',
            'departamentos'       => $role === 'departamento',
            'empleados'       => $role === 'empleado',
            default          => true,
        };*/

        return true;
    }

    public function getFilamentName(): string
    {
        return "{$this->nombre}";
    }
}
