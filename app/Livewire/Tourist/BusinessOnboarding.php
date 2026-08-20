<?php

namespace App\Livewire\Tourist;

use App\Models\ServiceSubmission;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class BusinessOnboarding extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public string $type = '';

    public string $rawInput = '';

    public $image = null;

    // AI Suggestions
    public string $suggestedTitle = '';

    public string $suggestedCategory = '';

    public string $suggestedExcerpt = '';

    public string $suggestedDescription = '';

    public float $suggestedPrice = 0;

    public int $suggestedDuration = 0;

    public int $suggestedAuthenticityScore = 0;

    public string $suggestedLocalTag = '';

    public array $suggestedContextTags = [];

    // Entity mapping from AI/Voice
    public ?string $voiceTitle = null;

    public ?float $voicePrice = null;

    public ?int $voiceDuration = null;

    public bool $isPerPerson = false;

    // Location suggested by voice/text
    public ?string $suggestedAddress = null;

    public ?float $suggestedLatitude = null;

    public ?float $suggestedLongitude = null;

    public bool $isProcessing = false;

    public bool $isTranscribing = false;

    protected $listeners = ['voiceTranscribed' => 'onVoiceTranscribed'];

    public function onVoiceTranscribed(array $data): void
    {
        $this->rawInput = $data['text'] ?? '';
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
    }

    public function selectType(string $type): void
    {
        $this->type = $type;
        $this->step = 2;
    }

    public function processWithAI(): void
    {
        $this->validate([
            'rawInput' => 'required|min:10',
        ]);

        $this->isProcessing = true;

        // Simulación de delay de IA
        usleep(1500000);

        // Lógica de "IA" simulada basada en el input
        $input = Str::lower($this->rawInput);

        $this->suggestedTitle = $this->voiceTitle ?? Str::title(explode(' ', $this->rawInput)[0].' '.(explode(' ', $this->rawInput)[1] ?? 'Local'));
        $this->suggestedCategory = $this->type;
        $this->suggestedExcerpt = Str::limit($this->rawInput, 80);

        $descSuffix = '. Una experiencia única en Lanzarote que no te puedes perder.';
        if ($this->isPerPerson) {
            $descSuffix = ' (Precio por persona). '.$descSuffix;
        }
        $this->suggestedDescription = $this->rawInput.$descSuffix;

        // Prioridad a lo detectado por voz, si no, regex o random
        if ($this->voicePrice) {
            $this->suggestedPrice = $this->voicePrice;
        } elseif (preg_match('/(\d+)/', $input, $matches)) {
            $this->suggestedPrice = (float) $matches[1];
        } else {
            $this->suggestedPrice = rand(15, 45);
        }

        if ($this->voiceDuration) {
            $this->suggestedDuration = $this->voiceDuration;
        } else {
            $this->suggestedDuration = rand(30, 120);
        }
        $this->suggestedAuthenticityScore = rand(70, 95);
        $this->suggestedLocalTag = match ($this->type) {
            'restaurant' => 'papas_mojo',
            'experience' => 'vino_volcanico',
            'product' => 'aloe_vera',
            'activity' => 'timanfaya_view',
            default => 'local_authentic'
        };
        $this->suggestedContextTags = ['local_authentic', 'high_quality'];

        $this->isProcessing = false;
        $this->step = 3;
    }

    public function nextStep(): void
    {
        $this->step++;
    }

    public function submit(): void
    {
        $submission = ServiceSubmission::create([
            'type' => $this->type,
            'raw_input_text' => $this->rawInput,
            'suggested_title' => $this->suggestedTitle,
            'suggested_category' => $this->suggestedCategory,
            'suggested_excerpt' => $this->suggestedExcerpt,
            'suggested_description' => $this->suggestedDescription,
            'suggested_address' => $this->suggestedAddress,
            'suggested_latitude' => $this->suggestedLatitude,
            'suggested_longitude' => $this->suggestedLongitude,
            'suggested_price_from' => $this->suggestedPrice,
            'suggested_duration_minutes' => $this->suggestedDuration,
            'suggested_authenticity_score' => $this->suggestedAuthenticityScore,
            'suggested_local_tag' => $this->suggestedLocalTag,
            'suggested_context_tags' => $this->suggestedContextTags,
            'status' => 'pending_review',
            'submitted_at' => now(),
            'ai_feedback' => [
                'clarity' => 'high',
                'market_fit' => 'excellent',
                'suggested_tags' => ['local', 'authentic', 'family'],
            ],
        ]);

        if ($this->image) {
            $path = $this->image->store('offers', 'public');
            $submission->update(['image_path' => $path]);
        }

        $this->step = 6; // Success step
    }

    public function render()
    {
        return view('livewire.tourist.business-onboarding');
    }
}
