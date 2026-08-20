<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sort',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function catalogItems(): HasMany
    {
        return $this->hasMany(WorkCatalog::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_work_category')->withTimestamps();
    }

    public function rentalContacts(): BelongsToMany
    {
        return $this->belongsToMany(RentalContact::class, 'rental_contact_work_category')->withTimestamps();
    }
}
