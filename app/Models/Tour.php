<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tour extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tours';

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
        'name',
        'tour_name',
        'description',
        'short_description',
        'location_id',
        'admin_id',
        'server_id',
        'duration_hours',
        'duration_days',
        'base_price',
        'discount_percentage',
        'max_capacity',
        'min_participants',
        'difficulty_level',
        'average_rating',
        'total_ratings',
        'main_image_url',
        'is_active',
        'is_featured',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration_hours' => 'decimal:2',
        'base_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id', 'id');
    }

    public function getNameAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['tour_name'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['tour_name'] = $value;
    }

    public function getAdminIdAttribute(): ?int
    {
        return $this->attributes['created_by'] ?? null;
    }

    public function setAdminIdAttribute(mixed $value): void
    {
        $this->attributes['created_by'] = $value;
    }

    /**
     * Get the location that owns the tour.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    /**
     * Get the user that created the tour.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Get the images for the tour.
     */
    public function images(): HasMany
    {
        return $this->hasMany(TourImage::class, 'tour_id', 'id');
    }

    /**
     * Get the schedules for the tour.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(TourSchedule::class, 'tour_id', 'id');
    }

    /**
     * Get the translations for the tour.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(TourTranslation::class, 'tour_id', 'id');
    }

    /**
     * Get the categories for the tour.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            TourCategory::class,
            'tour_category_mapping',
            'tour_id',
            'category_id',
            'id',
            'id'
        );
    }

    /**
     * Get the bookings for the tour.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(TourBooking::class, 'tour_id', 'id');
    }
    public function externalSource(): BelongsTo
    {
        return $this->belongsTo(NovaIntegrationSetting::class, 'external_source_id', 'id');
    }
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id', 'id');
    }
    public function externalSyncMappings(): HasMany
    {
        return $this->hasMany(ExternalSyncMapping::class, 'target_id', 'id')
            ->where('target_model', 'tour');
    }
}
