<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuilderSchema extends Model
{
    protected $fillable = ['model', 'schema'];
    public $timestamps = false;
}
