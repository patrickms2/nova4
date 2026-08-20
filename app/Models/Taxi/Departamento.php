<?php
// app/Models/Departamento.php
namespace App\Models\Taxi;

use App\Models\Taxi\Cita;
use App\Models\Taxi\Documento;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Filament\Panel\Concerns\HasTenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Taxi\Tipodoc;
use App\Models\Taxi\Ticket;
use App\Models\Taxi\DepartamentoHorarios;
use App\Models\Taxi\DepartamentoTipodoc;
//use App\Models\Taxi\Especialidad;
use App\Models\Taxi\Project;
use App\Models\Taxi\Usuario;
use Illuminate\Support\Collection;
use Zap\Models\Concerns\HasSchedules;

class Departamento extends Model
{
    use HasFactory;

    protected $table = 'departamentos';

    protected $fillable = [
        'usuario_id', 'operacion_id', 'descripcion', 'estado', 'color', 'is_destacado', 'ubicacion', 'portada',
        'experience', 'is_featured', 'nombre', 'image'
    ];
    public function getMenuLinkAttribute(): string
    {
        return route('departamentos.index', $this);
    }

    public function getMenuNameAttribute(): string
    {
        return $this->nombre;
    }
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }
    public function scopePorEncargado($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }
    public static function getFilamentSearchLabel(): string
    {
        return 'name';
    }
    public function tickets(){
        return $this->hasMany(Ticket::class, 'departamento_id');
    }
    public function citas(){
        return $this->hasMany(Cita::class);
    }
    public function departamentoHorarios(){
        return $this->hasMany(DepartamentoHorarios::class, 'departamento_id');
    }
    public function search(Builder $query, ?string $search): void
    {
        $query->when($search, function ($q, $search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('nombre', 'like', "%{$search}%");

            });
        });
    }
    /*public function especialidades(){
        return $this->belongsToMany(Especialidad::class, 'departamentos_especialidades', 'departamento_id', 'especialidad_id');
    }
    public function especialidad(){
        return $this->belongsTo(Especialidad::class,  'especialidad_id');
    }*/
    public function tiposdocs()
    {
        return $this->belongsToMany(Especialidad::class, 'departamentos_tiposdocs', 'departamento_id', 'tipo_doc_id');
    }
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'departamento_id');
    }
    public function proyectos()
    {
        return $this->hasMany(Project::class, 'departamento_id');
    }
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');;
    }
    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'departamento_id');
    }

    public function getFilamentName(): string
    {
        return "{$this->nombre}";
    }

    public function canAccessTenant(Model $tenant): bool
    {
       return true;
    }

    /*public function getTenants(Panel $panel): array|Collection
    {
        return \App\Models\Taxi\Departamento::select('nombre')->limit(5)->get()->toArray();
    }

    public function getCurrentTenantLabel(): string
    {
        return "{$this->nombre}";
    }*/
}
