<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Attributes\Rule;
use Livewire\Component;
use WallaceMartinss\FilamentEvolution\Models\WhatsappInstance;
use WallaceMartinss\FilamentEvolution\Services\EvolutionClient;
use WallaceMartinss\FilamentEvolution\Services\WhatsappService;

class TestWhatsApp extends Component
{
    public string $instanceId = '';

    public string $toNumber = '66988808418';

    #[Rule('required|string')]
    public string $message = '';

    public string $result = '';

    public bool $loading = false;

    public bool $success = false;

    public bool $error = false;

    public function mount(): void
    {
        $defaultInstance = WhatsappInstance::where('status', 'open')->first();
        if ($defaultInstance) {
            $this->instanceId = $defaultInstance->id;
        }
    }

    public function sendMessage(): void
    {
        $this->validate();

        $this->loading = true;
        $this->success = false;
        $this->error = false;
        $this->result = '';

        try {
            $service = app(WhatsappService::class);
            $instance = $service->getInstance($this->instanceId);
            if (! $instance) {
                throw new \Exception('Instance not found');
            }

            $client = app(EvolutionClient::class);

            $number = preg_replace('/\D/', '', $this->toNumber);
            $response = $client->sendText($instance->name, $number, $this->message);

            $this->result = json_encode($response, JSON_PRETTY_PRINT);
            $this->success = true;

            $this->dispatch('message-sent');
        } catch (\Exception $e) {
            $this->result = $e->getMessage();
            $this->error = true;

            $this->dispatch('message-failed');
        } finally {
            $this->loading = false;
        }
    }

    public function getInstanceOptionsProperty(): array
    {
        return WhatsappInstance::where('status', 'open')
            ->get()
            ->map(fn ($instance) => [
                'id' => $instance->id,
                'label' => "{$instance->name} ({$instance->number})",
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.test-whats-app');
    }
}
