<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\CredentialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Credential extends Model
{
    /** @use HasFactory<CredentialFactory> */
    use HasFactory;

    protected $fillable = ['person_id', 'type', 'name', 'identifier', 'secret', 'status', 'valid_from', 'valid_until', 'metadata'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return ['secret' => 'encrypted', 'valid_from' => 'datetime', 'valid_until' => 'datetime', 'metadata' => 'array'];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function accessGrants(): BelongsToMany
    {
        return $this->belongsToMany(AccessGrant::class)->withTimestamps();
    }

    public function maskedValue(): string
    {
        return filled($this->secret) ? Str::mask($this->secret, '•', 0, max(Str::length($this->secret) - 2, 0)) : '—';
    }

    public function isValidAt(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->status === 'active' && ($this->valid_from === null || $this->valid_from->lte($at)) && ($this->valid_until === null || $this->valid_until->gte($at));
    }
}
