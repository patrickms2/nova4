<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NovaRequest extends Model
{
        use HasFactory;

    protected $fillable = [
        'type','status','title','summary','context','user_id',
    ];

protected $casts = [
  'context' => 'array',
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
];
}