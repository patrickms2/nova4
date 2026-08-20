<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelPackage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'travel_packages';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agency_id',
        'package_name',
        'description',
        'duration_days',
        'base_price',
        'discount_percentage',
        'max_participants',
        'average_rating',
        'total_ratings',
        'main_image_url',
        'is_active',
        'is_featured',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration_days' => 'integer',
        'base_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'max_participants' => 'integer',
        'average_rating' => 'decimal:2',
        'total_ratings' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Get the agency that owns the package.
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(TravelAgency::class, 'agency_id', 'id');
    }

    /**
     * Get the destinations for the package.
     */
    public function destinations(): HasMany
    {
        return $this->hasMany(PackageDestination::class, 'package_id', 'id');
    }

    /**
     * Get the inclusions for the package.
     */
    public function inclusions(): HasMany
    {
        return $this->hasMany(PackageInclusion::class, 'package_id', 'id');
    }

    /**
     * Get the bookings for the package.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(PackageBooking::class, 'package_id', 'id');
    }
}
