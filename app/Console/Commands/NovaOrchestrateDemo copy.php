<?php

namespace App\Console\Commands;

use App\Services\Nova\NovaOrchestratorService;
use Illuminate\Console\Command;

class NovaOrchestrateDemo extends Command
{
    protected $signature = 'nova:orchestrate-demo
        {message=Quiero reservar mesa y luego una visita en La Geria mañana : Tourist message to simulate}
        {--phone=+34646426442 : Tourist WhatsApp phone}';

    protected $description = 'Run a local Sirvo + La Geria Nova orchestration demo';

    public function handle(NovaOrchestratorService $orchestrator): int
    {
        $result = $orchestrator->runLocalTourismScenario(
            message: (string) $this->argument('message'),
            touristPhone: (string) $this->option('phone'),
        );

        $this->info('Nova orchestration demo completed.');
        $this->line('Nova request ID: '.$result['nova_request_id']);
        $this->newLine();
        $this->line($result['reply']);
        $this->newLine();
        $this->line(json_encode($result['checks'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
