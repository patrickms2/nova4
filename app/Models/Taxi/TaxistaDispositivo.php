<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class TaxistaDispositivo extends Model
{
    use HasTags;

    protected $table = 'taxistas_dispositivos';
    protected $fillable = ["taxista_id","dispositivo_id"];


    /**
     * The table primary key field
     *
     * @var string
     */


    public function taxista()
    {
        return $this->belongsTo(Taxista::class, 'taxista_id');
    }

    public function dispositivo()
    {
        return $this->belongsTo(Device::class, 'dispositivo_id');
    }


}
