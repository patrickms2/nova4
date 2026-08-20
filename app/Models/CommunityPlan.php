<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'community_id',
        'name',
        'description',
        'valid_from',
        'valid_until',
        'status',
        'replaced_by_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CommunityPlanItem::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
    public function tasks(): HasMany
    {
        return $this->hasMany(WorkOrderTask::class);
    }
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
    public function days(): HasMany
    {
        return $this->hasMany(CommunityPlanDay::class);
    }
    public function catalogs(): HasMany
    {
        return $this->hasMany(CommunityPlanItem::class,'work_catalog_id');
    }
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_id');
    }

    public function replaces(): HasMany
    {
        return $this->hasMany(self::class, 'replaced_by_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
