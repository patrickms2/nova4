<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkCatalog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'work_catalog';

    protected $fillable = [
        'work_category_id',
        'code',
        'title',
        'instructions',
        'requirements',
        'default_priority',
        'active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkCategory::class,'work_category_id');
    }

    public function planItems(): HasMany
    {
        return $this->hasMany(CommunityPlanItem::class);
    }
}
