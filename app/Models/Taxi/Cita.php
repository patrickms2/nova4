<?php

declare(strict_types=1);

// app/Models/Cita.php

namespace App\Models\Taxi;

use App\Enums\CitaStatus;
use App\Enums\CitasTipos;
use App\Events\CitaCancelada;
use App\Events\CitaConfirmada;
use Carbon\Carbon;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

final class Cita extends Model implements Eventable
{
    use Notifiable;

    protected $table = 'usuarios_appointments';

    protected $fillable = [
        'id', 'departamento_id', 'usuario_id', 'appointment_date', 'appointment_time', 'status', 'appointment_type', 'slot_id', 'title', 'name', 'tipo_id', 'position', 'notes',
    ];

    protected $casts = [
        'appointment_time' => 'datetime:H:i',
        'slot_id' => 'datetime:H:i',
        'appointment_date' => 'datetime',
        'position' => 'integer',
        'title' => 'string',
        'status' => CitaStatus::class,
        'appointment_type' => CitasTipos::class,
        'tipo_id' => 'integer',
    ];
    protected $appends = ['nombre'];
    protected $with = ['usuario:id,nombre', 'tipo:id,nombre,color,icono', 'departamento:id,nombre,color,usuario_id'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id')
            ->select(['id', 'nombre', 'tipo_id']);
    }

    public function setNombreAttribute($value)
    {
        if (isset($this->usuario->nombre)) {
            $this->attributes['nombre'] = $this->usuario->nombre;
        } elseif (isset($this->tipo->nombre)) {
            $this->attributes['nombre'] = $this->tipo->nombre;
        } elseif (is_null($value)) {
            $this->attributes['nombre'] = 'Tipo';
        } else {
            $this->attributes['nombre'] = $value;
        }
    }

    public function tipo()
    {
        return $this->belongsTo(TipoCitas::class, 'tipo_id')
            ->select(['id', 'nombre']);
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id')
            ->select(['id', 'nombre', 'usuario_id']);
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
            $query->where('appointment_date', 'like', $searchTerm)
                ->orWhere('appointment_time', 'like', $searchTerm)
                ->orWhereHas('departamento.usuario', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', $searchTerm);
                })
                ->orWhereHas('tipo', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', $searchTerm);
                })
                ->orWhereHas('usuario', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', $searchTerm);
                });
        });
    }

    public function confirmar(): void
    {
        $this->update(['status' => 'confirmada']);
    }

    public function cancelar(?string $motivo = null): void
    {
        $this->update([
            'status' => 'cancelada',
            'notes' => $motivo ? ($this->notes ? $this->notes . "\nMotivo de cancelacion: " . $motivo : 'Motivo de cancelacion: ' . $motivo) : $this->notes,
        ]);
    }

    public function esConfirmada(): bool
    {
        return $this->status === 'confirmada';
    }

    public function esCancelada(): bool
    {
        return $this->status === 'cancelada';
    }

    public function esPendiente(): bool
    {
        return $this->status === 'pendiente';
    }

    public function esFinalizada(): bool
    {
        return $this->status === 'finalizada';
    }

    public function scopeParaSemana($query, $days = 7)
    {
        return $query->where('appointment_date', '>=', now())
            ->where('appointment_date', '<=', now()->addDays($days))
            ->orderBy('appointment_date');
    }

    public function scopeParaHoy($query)
    {
        return $query->whereDate('appointment_date', today());
    }

    public function scopePorDepartamento($query, $departamentoId)
    {
        return $query->where('departamento_id', $departamentoId);
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopePorEncargado($query, $usuarioId)
    {
        return $query->with(['usuario', 'departamento'])->join('departamentos as d', 'd.id', '=', 'usuarios_appointments.departamento_id')
            ->where('d.usuario_id', $usuarioId);
    }

    public function getFechaAttribute()
    {
        return $this->appointment_date->format('d/m/Y') . ' ' . $this->slot_id->format('H:i');
    }

    public function getHoraAttribute()
    {
        return $this->slot_id->format('H:i');
    }

    public function getNombreAttribute(): string
    {
        return $this->tipo->nombre;

    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function toCalendarEvent(): CalendarEvent
    {
        $departamento = Departamento::find($this->departamento_id);
        $usuario = Usuario::find($this->usuario_id);

        $fin = Carbon::parse($this->appointment_date);
        $fin = $fin->format('Y-m-d');
        $minutos_fin = Carbon::parse($fin . ' ' . Carbon::parse($this->slot_id)->format('H:i'));
        $minutos_fin = $minutos_fin->format('Y-m-d H:i');
        $hora_fin = Carbon::parse($minutos_fin)->addMinutes(60);
        $hora_fin = $hora_fin->format('Y-m-d H:i');

        return CalendarEvent::make($this)
            ->title($this->id . ' ' . $departamento->nombre . ' ' . $usuario->nombre)
            ->start($minutos_fin)
            ->end($hora_fin)
            ->backgroundColor('red');
    }

    public function unassigned(): bool
    {
        return is_null($this->usuario_id);
    }

    public function initials_tipo(): ?string
    {
        return $this->tipo->nombre
            ? Str::of($this->tipo->nombre)
                ->explode(' ')
                ->take(2)
                ->map(fn($word) => Str::substr($word, 0, 1))
                ->implode('')
            : null;
    }

    public function initials(): ?string
    {
        return $this->usuario->nombre
            ? Str::of($this->usuario->nombre)
                ->explode(' ')
                ->take(2)
                ->map(fn($word) => Str::substr($word, 0, 1))
                ->implode('')
            : null;
    }

    protected static function booted()
    {

        self::updating(function ($model) {
            // $model->title = $model->id.' '.$model->departamento->nombre.' '.$model->usuario->nombre;

        });
        self::updated(function ($cita) {
            if ($cita->isDirty('status')) {
                if ($cita->status === 'confirmada') {
                    event(new CitaConfirmada($cita));
                } elseif ($cita->status === 'cancelada') {
                    event(new CitaCancelada($cita));
                }
            }
        });
        self::creating(function ($model) {
            $model->appointment_date = now();
            $model->appointment_time = now();
            $model->title = $model->departamento . ' ' . $model->usuario;
        });
    }
}
