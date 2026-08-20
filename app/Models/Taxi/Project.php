<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Taxi\Task;
use App\Models\Taxi\Note;
use App\Models\User;
class Project extends Model
{
    use SoftDeletes;

    protected $casts = [
        'completed_date' => 'date',
        'start_date' => 'date',
        'due_date' => 'date',
        'attachments' => 'array',
        'attachment_file_names' => 'array',
    ];
    protected $table = 'usuarios_proyectos';

    protected $guarded = ['id'];



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function openTasks(): HasMany
    {
        return $this->hasMany(Task::class)
            ->where('status', '!=', 'completed');
    }

}
