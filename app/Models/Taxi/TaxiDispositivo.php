<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class TaxiDispositivo extends Model
{
    use HasTags;

    protected $table = 'taxis_dispositivos';
    protected $fillable = ["taxi_id","dispositivo_id"];


    /**
     * The table primary key field
     *
     * @var string
     */


    public function taxi()
    {
        return $this->belongsTo(Taxi::class, 'taxi_id');
    }

    public function dispositivo()
    {
        return $this->belongsTo(Device::class, 'dispositivo_id');
    }


}
