<?php

namespace App\Models\Taxi;

use App\Enums\TaskStatusEnum;
use App\Models\User;
use App\Observers\TaskObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([TaskObserver::class])]
class Task extends Model /*implements Eventable*/
{
    use SoftDeletes;
    protected $table = 'usuarios_tareas';

    protected $guarded = ['id'];
    protected $fillable = [
        'title',
        'description',
        'user_id',
        'project_id',
        'usuario_id',
        'departamento_id',
        'status',
        'completed_date',
        'due_date',
        'attachments',
    ];
    protected $casts = [
        'completed_date' => 'date',
        'start_date' => 'date',
        'due_date' => 'date',
        'attachments' => 'array',
        'attachment_file_names' => 'array',
        'status' => TaskStatusEnum::class,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function departamento(){
        return $this->belongsTo( Departamento::class,);
    }
    public function user(){
        return $this->belongsTo(User::class,"user_id");
    }
    public function usuario(){
        return $this->belongsTo(Usuario::class,"usuario_id");
    }
    /*public function toCalendarEvent(): CalendarEvent|array {
        return new CalendarEvent(
            id: (string) $this->id,
            title: $this->title ?? 'Sin título',
            start: $this->start_date,
            end: $this->due_date,
            allDay: false,
            extendedProps: [
                'model' => Task::class,
                'key' => $this->id,
                'action' => 'edit'
            ]
        );
    }*/
}
