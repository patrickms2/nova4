<?php

namespace App\Models\Taxi;

use App\Enums\CitasPriorityEnum;
use App\Enums\TicketStatus;
//use App\Observers\TicketObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
//use Illuminate\Database\Eloquent\Attributes\ObservedBy;

//#[ObservedBy([TicketObserver::class])]
class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;


    protected $table = 'usuarios_tickets';

protected $fillable = [
        'name',
        'description',
        'usuario_id',
    'creado_por',
    'tipo_id',
    'departamento_id',
    'municipio_id',
    'status',
    'lugar',
    'url',
        'start_date',
        'end_date',
        'completed_date',
        'priority',
        'attachments',
        'attachment_file_names',
    ];
    protected $casts = [
        'completed_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'attachments' => 'array',
        'attachment_file_names' => 'array',
        'status' => TicketStatus::class,
        'priority' => CitasPriorityEnum::class,
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    public function departamento(){
        return $this->belongsTo(Departamento::class,"departamento_id");
    }
    public function tipo(){
        return $this->belongsTo(TipoTickets::class,"tipo_id");
    }
    public function creadopor(){
        return $this->belongsTo(Usuario::class,"creado_por");
    }
    public function usuario(){
        return $this->belongsTo(Usuario::class,"usuario_id");
    }
    public function municipio(){
        return $this->belongsTo(Municipio::class,"municipio_id");
    }
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
    /*public function toCalendarEvent2(): CalendarEvent
    {
        $departamento = Departamento::find($this->departamento_id);
        $usuario =  Usuario::find($this->usuario_id);
        $fin = Carbon::parse($this->end_date);
        $fin = $fin->format('Y-m-d H:i');
        $minutos_fin = Carbon::parse($this->start_date)->format('Y-m-d H:i');
        $hora_fin = Carbon::parse($fin)->addMinutes(60);
        $hora_fin = $hora_fin->format('Y-m-d H:i');


        return CalendarEvent::make($this)
            ->title($this->id.' '.$departamento->nombre.' '.$usuario->nombre)
            ->start($hora_fin)
            ->end($fin)
            ->backgroundColor('blue');
    }*/
}
