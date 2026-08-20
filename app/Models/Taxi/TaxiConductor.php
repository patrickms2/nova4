<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;
use App\Models\Taxi\Taxi;
use App\Models\Taxi\Conductor;

class TaxiConductor extends Model
{
    use HasTags;

	protected $table = 'taxis_conductores';
	protected $fillable = ["conductor_id","taxi_id"];


	/**
     * The table primary key field
     *
     * @var string
     */


    public function taxi()
    {
        return $this->belongsTo(Taxi::class, 'taxi_id');
    }

    public function conductor()
    {
        return $this->belongsTo(Conductor::class, 'conductor_id');
    }

}
