<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class TipoTarea extends Model
{
    use HasFactory;
    protected $table = 'tipos_tareas';

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
    }

    protected $casts = [
        'attributes' => 'array'
    ];

    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(TipoTarea::class, 'parent_id', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
