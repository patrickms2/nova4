<?php
declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaRelationType: string
{
    case BelongsTo = 'belongs_to';
    case HasOne = 'has_one';
    case HasMany = 'has_many';
    case BelongsToMany = 'belongs_to_many';
    case MorphOne = 'morph_one';
    case MorphMany = 'morph_many';
    case MorphTo = 'morph_to';
    case Custom = 'custom';
}
