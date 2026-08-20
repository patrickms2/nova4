<?php

namespace App\Livewire\Providers;

use App\Models\ServiceSubmission;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ServiceSubmissionWizard extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public ?string $type = null;

    public ?string $rawInputText = null;

    public ?string $suggestedTitle = null;

    public ?string $suggestedCategory = null;

    public ?string $suggestedExcerpt = null;

    public ?string $suggestedDescription = null;

    public ?float $suggestedPriceFrom = null;

    public ?int $suggestedDurationMinutes = null;

    // Entity mapping from AI/Voice
    public ?string $voiceTitle = null;

    public ?float $voicePrice = null;

    public ?int $voiceDuration = null;

    public bool $isPerPerson = false;

    // Location suggested by voice/text
    public ?string $suggestedAddress = null;

    public ?float $suggestedLatitude = null;

    public ?float $suggestedLongitude = null;

    public $image = null;

    public bool $processing = false;

    public bool $submitted = false;

    public bool $isTranscribing = false;

    protected $listeners = ['voiceTranscribed' => 'onVoiceTranscribed'];

    public function onVoiceTranscribed(array $data): void
    {
        $this->rawInputText = $data['text'] ?? '';
        $analysis = $data['analysis'] ?? [];

        if (isset($analysis['suggested_type']) && in_array($analysis['suggested_type'], ['restaurant', 'experience', 'product', 'activity', 'artisan'])) {
            $this->type = $analysis['suggested_type'];
        }

        $this->voiceTitle = $analysis['suggested_title'] ?? null;
        $this->voicePrice = $analysis['suggested_price'] ?? null;
        $this->voiceDuration = $analysis['suggested_duration'] ?? null;
        $this->isPerPerson = $analysis['is_per_person'] ?? false;
        $this->suggestedAddress = $analysis['suggested_address'] ?? null;
        $this->suggestedLatitude = $analysis['suggested_latitude'] ?? null;
        $this->suggestedLongitude = $analysis['suggested_longitude'] ?? null;

        $this->isTranscribing = false;

        if ($this->step === 2) {
            $this->processInput();
        }
    }

    public array $types = [
        'restaurant' => 'Restaurante',
        'experience' => 'Experiencia',
        'activity' => 'Actividad',
        'artisan' => 'Artesanía',
        'product' => 'Producto',
        'service' => 'Servicio',
    ];

    public function selectType(string $type): void
    {
        $this->type = $type;
        $this->step = 2;
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function processInput(): void
    {
        $this->validate([
            'type' => ['required', 'string'],
            'rawInputText' => ['required', 'string', 'min:10'],
        ]);

        $this->processing = true;

        // Simulación IA v1
        $this->suggestedTitle = $this->voiceTitle ?? $this->buildSuggestedTitle();
        $this->suggestedCategory = $this->mapSuggestedCategory();
        $this->suggestedExcerpt = $this->buildSuggestedExcerpt();
        $this->suggestedDescription = $this->buildSuggestedDescription();
        $this->suggestedPriceFrom = $this->voicePrice ?? $this->buildSuggestedPrice();
        $this->suggestedDurationMinutes = $this->voiceDuration ?? $this->buildSuggestedDuration();

        $this->processing = false;
        $this->step = 3;
    }

    public function acceptSuggestion(): void
    {
        $this->step = 4;
    }

    public function submitProposal(): void
    {
        $path = null;

        if ($this->image) {
            $path = $this->image->store('offers', 'public');
        }

        ServiceSubmission::create([
            'uuid' => (string) Str::uuid(),
            'type' => $this->type,
            'raw_input_text' => $this->rawInputText,
            'suggested_title' => $this->suggestedTitle,
            'suggested_category' => $this->suggestedCategory,
            'suggested_excerpt' => $this->suggestedExcerpt,
            'suggested_description' => $this->suggestedDescription,
            'suggested_address' => $this->suggestedAddress,
            'suggested_latitude' => $this->suggestedLatitude,
            'suggested_longitude' => $this->suggestedLongitude,
            'suggested_price_from' => $this->suggestedPriceFrom,
            'suggested_duration_minutes' => $this->suggestedDurationMinutes,
            'image_path' => $path,
            'status' => 'pending_review',
            'ai_feedback' => [
                'message' => 'La propuesta parece clara, útil y alineada con el ecosistema.',
            ],
            'submitted_at' => now(),
        ]);

        $this->submitted = true;
        $this->step = 5;
    }

    protected function buildSuggestedTitle(): string
    {
        return match ($this->type) {
            'artisan' => 'Artesanía local con historia',
            'restaurant' => 'Sabores auténticos de Lanzarote',
            'experience' => 'Experiencia local para descubrir Lanzarote',
            'activity' => 'Actividad recomendada para tu llegada',
            'product' => 'Producto local con identidad de Lanzarote',
            default => 'Propuesta local para descubrir Lanzarote',
        };
    }

    protected function mapSuggestedCategory(): string
    {
        return match ($this->type) {
            'artisan' => 'product',
            'restaurant' => 'restaurant',
            'experience' => 'experience',
            'activity' => 'activity',
            'product' => 'product',
            default => 'service',
        };
    }

    protected function buildSuggestedExcerpt(): string
    {
        return match ($this->type) {
            'artisan' => 'Hecho a mano, auténtico y fácil de descubrir para el visitante.',
            'restaurant' => 'Una propuesta gastronómica muy nuestra, pensada para decidir rápido.',
            'experience' => 'Una forma sencilla de acercar al visitante a lo auténtico.',
            'activity' => 'Ideal para activar la llegada con algo útil y memorable.',
            'product' => 'Producto local con valor cultural y atractivo para el visitante.',
            default => 'Una propuesta clara, local y fácil de integrar en TAXILANZ.',
        };
    }

    protected function buildSuggestedDescription(): string
    {
        $descSuffix = '. Una propuesta clara, local y fácil de integrar en TAXILANZ.';
        if ($this->isPerPerson) {
            $descSuffix = ' (Precio por persona). '.$descSuffix;
        }

        return trim("Basado en tu descripción, esta propuesta puede presentarse como una experiencia local clara, atractiva y fácil de descubrir dentro del ecosistema TAXILANZ. {$this->rawInputText}".$descSuffix);
    }

    protected function buildSuggestedPrice(): float
    {
        return match ($this->type) {
            'artisan' => 25,
            'restaurant' => 20,
            'experience' => 35,
            'activity' => 18,
            'product' => 12,
            default => 15,
        };
    }

    protected function buildSuggestedDuration(): int
    {
        return match ($this->type) {
            'restaurant' => 60,
            'experience' => 90,
            'activity' => 45,
            default => 30,
        };
    }

    public function render()
    {
        return view('livewire.providers.service-submission-wizard');
    }
}
