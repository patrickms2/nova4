<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NovaContext extends Model
{
    protected $fillable = ['user_id','key','label','is_default','order'];

    public function shortcuts()
    {
        return $this->hasMany(NovaShortcut::class)->orderBy('order');
    }
}