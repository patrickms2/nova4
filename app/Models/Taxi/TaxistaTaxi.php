<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class TaxistaTaxi extends Model
{
    use HasTags;

	protected $table = 'taxis_conductores';
	protected $fillable = ["taxista_id","taxi_id"];


	/**
     * The table primary key field
     *
     * @var string
     */


    public function taxi()
    {
        return $this->belongsTo(Taxi::class, 'taxi_id');
    }

    public function taxista()
    {
        return $this->belongsTo(Taxista::class, 'taxista_id');
    }

}
