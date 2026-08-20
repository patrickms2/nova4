<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class NovaWhatsappWebhookDemo extends Command
{
    protected $signature = 'nova:whatsapp-webhook-demo
        {message=Hola, quiero reservar mesa y una visita en La Geria mañana : Message to send to the local webhook}
        {--phone=+34646426442 : WhatsApp phone}
        {--url=https://48ee-2-137-246-137.ngrok-free.app/api/nova/whatsapp/webhook : Webhook URL}';

    protected $description = 'Send a local WhatsApp-like payload to the Nova webhook';

    public function handle(): int
    {
        $url = (string) $this->option('url');
        $token = config('services.nova.webhook_token');

        $request = Http::withoutVerifying()
            ->acceptJson()
            ->asJson();

        if ($token) {
            $request = $request->withHeader('X-Nova-Webhook-Token', $token);
        }

        $response = $request->post($url, [
            'phone' => (string) $this->option('phone'),
            'message' => (string) $this->argument('message'),
            'source' => 'local_demo',
        ]);

        $this->line('Status: '.$response->status());
        $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response->successful() ? self::SUCCESS : self::FAILURE;
    }
}
