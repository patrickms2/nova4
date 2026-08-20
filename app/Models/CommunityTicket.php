<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityTicket extends Model
{
    use HasFactory;

    protected $fillable = ['community_id','type', 'person_id', 'property_id', 'community_department_id', 'work_category_id', 'work_catalog_id', 'title', 'description', 'type', 'amount', 'attachment_path', 'priority', 'status', 'due_at', 'resolved_at', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'due_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(CommunityProperty::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(CommunityDepartment::class, 'community_department_id');
    }

    public function workCategory(): BelongsTo
    {
        return $this->belongsTo(WorkCategory::class);
    }

    public function workCatalog(): BelongsTo
    {
        return $this->belongsTo(WorkCatalog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
