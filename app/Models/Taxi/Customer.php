<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Customer extends Model
{
    use HasFactory;
    protected $table = 'shop_customers';

    protected $fillable = [
        'id',
        'name',
        'status',
    ];

}
