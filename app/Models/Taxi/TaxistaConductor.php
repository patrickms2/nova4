<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;
use App\Models\Taxi\Conductor;
use App\Models\Taxi\Taxista;

class TaxistaConductor extends Model
{
    use HasTags;

	protected $table = 'taxis_conductores';
	protected $fillable = ["taxista_id","conductor_id"];


	/**
     * The table primary key field
     *
     * @var string
     */


    public function conductor()
    {
        return $this->belongsTo(Usuario::class, 'conductor_id','id');
    }

    public function taxista()
    {
        return $this->belongsTo(Taxista::class, 'taxista_id');
    }

}
