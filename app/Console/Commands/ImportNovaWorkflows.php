<?php

namespace App\Console\Commands;

use Heiner\FilamentAgenticChatbot\Models\AgentWorkflow;
use Heiner\FilamentAgenticChatbot\Models\AgentWorkflowVersion;
use Illuminate\Console\Command;

class ImportNovaWorkflows extends Command
{
    protected $signature = 'nova:import-workflows';
    protected $description = 'Importar workflows de Nova desde JSON como drafts';

    public function handle()
    {
        $workflows = [
            [
                'name' => 'Nova Complete Workflow',
                'description' => 'Workflow completo que integra todos los recursos de Nova (intents, prompts, cross-selling, MCP, normalización, registro)',
                'file' => 'docs/nova-workflow-examples/nova-complete-workflow.json',
            ],
            [
                'name' => 'Nova Master Router',
                'description' => 'Workflow maestro con detección de intención Filament',
                'file' => 'docs/nova-workflow-examples/nova-master-router.json',
            ],
            [
                'name' => 'Nova Intent Detect',
                'description' => 'Workflow de detección de intención con MCP La Geria',
                'file' => 'docs/nova-workflow-examples/nova-intent-detect.json',
            ],
            [
                'name' => 'La Geria Agent',
                'description' => 'Visitas bodega y servicios La Geria',
                'file' => 'docs/nova-workflow-examples/la-geria-agent.json',
            ],
            [
                'name' => 'Sirvo Restaurantes',
                'description' => 'Reservas de restaurantes Sirvo',
                'file' => 'docs/nova-workflow-examples/sirvo-restaurantes.json',
            ],
            [
                'name' => 'Taxilanz Hoteles',
                'description' => 'Lista de hoteles Taxilanz',
                'file' => 'docs/nova-workflow-examples/taxilanz-hoteles.json',
            ],
            [
                'name' => 'Lanzaloe Magento',
                'description' => 'Productos y pedidos Lanzaloe',
                'file' => 'docs/nova-workflow-examples/lanzaloe-magento.json',
            ],
            [
                'name' => 'Taxilanz Transfers',
                'description' => 'Transferencias Taxilanz',
                'file' => 'docs/nova-workflow-examples/taxilanz-transfers.json',
            ],
        ];

        $this->info('Importando workflows de Nova...');

        foreach ($workflows as $wf) {
            if (! file_exists($wf['file'])) {
                $this->warn("Archivo no encontrado: {$wf['file']}");
                continue;
            }

            $json = file_get_contents($wf['file']);
            $snapshot = json_decode($json, true);

            if (! $snapshot) {
                $this->warn("Error decodificando JSON: {$wf['file']}");
                continue;
            }

            $workflow = AgentWorkflow::withoutEvents(function () use ($wf) {
                return AgentWorkflow::create([
                    'name' => $wf['name'],
                    'description' => $wf['description'],
                    'is_active' => false,
                ]);
            });

            $version = AgentWorkflowVersion::create([
                'agent_workflow_id' => $workflow->id,
                'version_number' => 1,
                'workflow_data' => $snapshot,
                'schema_version' => 1,
            ]);

            $this->info("✅ {$wf['name']} (ID: {$workflow->id}) - Draft creado");
        }

        $this->newLine();
        $this->info('Para publicar los workflows:');
        $this->info('1. Ve a Filament: https://novahubmcp.test/admin');
        $this->info('2. Navega a Agentic Chatbot -> Workflows');
        $this->info('3. Editar cada workflow y click en Publish');

        return Command::SUCCESS;
    }
}
