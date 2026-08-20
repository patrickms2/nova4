<?php

namespace App\Models;

use App\Services\SpeechAnalysisService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServiceSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type',
        'raw_input_text',
        'raw_input_audio_path',
        'suggested_title',
        'suggested_category',
        'suggested_excerpt',
        'suggested_description',
        'suggested_address',
        'suggested_latitude',
        'suggested_longitude',
        'suggested_price_from',
        'price_unit',
        'suggested_duration_minutes',
        'image_path',
        'status',
        'ai_feedback',
        'submitted_at',
        'approved_at',
        'suggested_authenticity_score',
        'suggested_local_tag',
        'suggested_context_tags',
    ];

    protected $casts = [
        'suggested_price_from' => 'decimal:2',
        'suggested_latitude' => 'decimal:7',
        'suggested_longitude' => 'decimal:7',
        'ai_feedback' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'suggested_authenticity_score' => 'integer',
        'suggested_context_tags' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (ServiceSubmission $submission) {
            if (blank($submission->uuid)) {
                $submission->uuid = (string) Str::uuid();
            }
        });

        // Autocompletar imagen y ubicación básica si faltan al guardar
        static::saving(function (ServiceSubmission $submission) {
            try {
                // Si no hay imagen, intentar sugerir una a partir del texto
                if (blank($submission->image_path)) {
                    $text = (string) ($submission->raw_input_text
                        ?: ($submission->suggested_description
                            ?: ($submission->suggested_excerpt ?: $submission->suggested_title)));

                    if (trim($text) !== '') {
                        $svc = app(SpeechAnalysisService::class);
                        $analysis = $svc->analyze($text);
                        if (! empty($analysis['suggested_image_url'])) {
                            $submission->image_path = $analysis['suggested_image_url'];
                        }

                        // Si hay dirección pero faltan coords, o viceversa, completar
                        if (blank($submission->suggested_address) && ! empty($analysis['suggested_address'])) {
                            $submission->suggested_address = $analysis['suggested_address'];
                        }
                        if (blank($submission->suggested_latitude) && ! empty($analysis['suggested_latitude'])) {
                            $submission->suggested_latitude = $analysis['suggested_latitude'];
                        }
                        if (blank($submission->suggested_longitude) && ! empty($analysis['suggested_longitude'])) {
                            $submission->suggested_longitude = $analysis['suggested_longitude'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // No romper el guardado si el análisis falla
            }
        });
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SubmissionReview::class);
    }
}
