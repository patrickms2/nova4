<?php

namespace App\Filament\App\NovaHub\Pages;

use Filament\Support\Icons\Heroicon;

use App\Models\Server;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class McpBusinessHub extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;



    protected static ?string $navigationLabel = 'Business Hub';

    protected static ?string $title = 'MCP Business Hub';

    protected static ?string $slug = 'mcp-business-hub';

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.pages.mcp-business-hub';

    public function getViewData(): array
    {
        $allServers = Server::query()
            ->with([
                'tools' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'prompts' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $novaServer = $allServers->firstWhere('slug', 'nova');
        $agentServers = $allServers->where('slug', '!=', 'nova')->values();

        $totals = [
            'servers' => $allServers->count(),
            'tools' => $allServers->sum(fn (Server $s) => $s->tools->count()),
            'prompts' => $allServers->sum(fn (Server $s) => $s->prompts->count()),
        ];

        $mermaid = $this->buildMermaidDiagram($novaServer, $agentServers);

        return compact('novaServer', 'agentServers', 'totals', 'mermaid');
    }

    private function buildMermaidDiagram(?Server $novaServer, Collection $agentServers): string
    {
        $lines = [];
        $lines[] = 'graph LR';
        $lines[] = '    NOVA(["Nova\nOrquestador"])';
        $lines[] = '    style NOVA fill:#3b82f6,stroke:#1d4ed8,color:#fff,font-weight:bold';

        if ($novaServer && $novaServer->prompts->isNotEmpty()) {
            $lines[] = '    subgraph NOVAPROMPTS ["Prompts del agente"]';
            foreach ($novaServer->prompts as $prompt) {
                $pid = 'NP'.$prompt->id;
                $label = $this->mermaidLabel($prompt->name);
                $lines[] = "    {$pid}[/\"{$label}\"/]";
                $lines[] = "    style {$pid} fill:#fef3c7,stroke:#f59e0b,color:#92400e";
            }
            $lines[] = '    end';
            $lines[] = '    NOVA --- NOVAPROMPTS';
        }

        foreach ($agentServers as $server) {
            $sid = 'S'.$server->id;
            $toolCount = $server->tools->count();
            $promptCount = $server->prompts->count();
            $label = $this->mermaidLabel($server->name);
            $lines[] = "    {$sid}[\"{$label}\n{$toolCount} tools\"]";
            $lines[] = "    style {$sid} fill:#f0fdf4,stroke:#16a34a,color:#166534";

            if ($server->prompts->isNotEmpty()) {
                foreach ($server->prompts as $prompt) {
                    $ppid = 'P'.$prompt->id;
                    $plabel = $this->mermaidLabel($prompt->name);
                    $lines[] = "    {$ppid}[/\"{$plabel}\"/]";
                    $lines[] = "    style {$ppid} fill:#fef3c7,stroke:#f59e0b,color:#92400e";
                    $lines[] = "    {$sid} --> {$ppid}";
                }
            }

            foreach ($server->tools->take(5) as $tool) {
                $tid = 'T'.$tool->id;
                $tlabel = $this->mermaidLabel($tool->name);
                $lines[] = "    {$tid}[\"{$tlabel}\"]";
                $lines[] = "    style {$tid} fill:#eff6ff,stroke:#3b82f6,color:#1e40af";
                $lines[] = "    {$sid} --> {$tid}";
            }

            if ($toolCount > 5) {
                $moreId = 'MORE'.$server->id;
                $more = $toolCount - 5;
                $lines[] = "    {$moreId}[\"+ {$more} tools\"]";
                $lines[] = "    style {$moreId} fill:#f3f4f6,stroke:#9ca3af,color:#6b7280";
                $lines[] = "    {$sid} --> {$moreId}";
            }

            $lines[] = "    NOVA --> {$sid}";
        }

        return implode("\n", $lines);
    }

    private function mermaidLabel(string $text): string
    {
        return str_replace(['"', "'", '(', ')', '\n', '\r'], [' ', ' ', '-', '-', ' ', ''], $text);
    }

    public static function chatUrl(Server $server): string
    {
        return route('filament.admin.pages.server-chat', ['server' => $server->id]);
    }
}
