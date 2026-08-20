<?php

namespace App\Models\Taxi;

use App\Models\Taxi\Servicio;
use App\Models\Taxi\Taxi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Extra
 *
 * @property $id
 * @property $slug
 * @property $orden
 * @property $descripcion
 * @property $icono
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Extra extends Model
{
    protected $table = 'taxi_extras';

    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['id', 'slug', 'orden', 'descripcion', 'icono'];

    public function taxis()
    {
        return $this->hasMany(Taxi::class, 'extras');
    }
}
