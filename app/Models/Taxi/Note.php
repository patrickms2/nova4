<?php

namespace App\Models\Taxi;

use App\Enums\NotaStatus;
use App\Enums\TiposTicket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use SoftDeletes;

    protected $table = 'notes';

    protected $fillable = [
        'id', 'user_id', 'ticket_id', 'task_id', 'project_id', 'note', 'created_at', 'updated_at', 'deleted_at', 'usuario_id', 'departamento_id', 'cita_id', 'group', 'status', 'model_id', 'model_type', 'user_type', 'title', 'body', 'background', 'border', 'color', 'checklist', 'icon', 'font_size', 'font', 'date', 'time', 'is_public', 'is_pined', 'order', 'place_in',
        'parent_id',
    ];

    protected $casts = [
        'status' => NotaStatus::class,
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'parent_id');
    }
}
