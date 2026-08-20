<?php

namespace App\Livewire;

use App\Models\Prompt;
use App\Models\Server;
use App\Models\Tool;
use App\Services\ToolExecutor;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Filament\Forms\Components\VoiceInput;

class ServerChat extends Component
{
    private const MAX_RESULT_BYTES = 120000;

    public ?int $serverId = null;

    public ?Server $server = null;

    public ?int $toolId = null;

    public ?int $promptId = null;

    public string $message = '';

    public array $messages = [];

    public bool $isLoading = false;

    public ?string $error = null;

    public string $selectedTab = 'answer';

    public array $executionSteps = [];

    public array $workflowPlan = [];

    public function mount(?int $serverId = null): void
    {
        $this->serverId = $serverId;
        $this->promptId = request()->integer('prompt') ?: request()->integer('prompt_id') ?: null;
        $this->loadServer();
    }

    public function loadServer(): void
    {
        $this->server = Server::query()
            ->with([
                'tools' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                'prompts' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            ])
            ->find($this->serverId);

        $this->promptId = $this->resolvePromptId();
        $this->toolId = null;
        $this->workflowPlan = [];
    }

    public function send(): void
    {
        $this->validate([
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $userMessage = trim($this->message);

        $this->messages[] = [
            'role' => 'user',
            'tool' => $this->activePromptTitle(),
            'content' => $userMessage,
        ];

        $this->message = '';
        $this->isLoading = true;
        $this->error = null;
        $this->executionSteps = [];

        // Nova main server: handle meta-queries about connections/agents inline
        if ($this->isNovaServer() && $this->isMetaQuery($userMessage)) {
            $this->handleNovaMetaResponse($userMessage);
            $this->isLoading = false;

            return;
        }

        $wasToolForced = $this->toolId !== null;
        $tool = $this->selectToolFor($userMessage);
        $this->toolId = $tool->id;
        $this->workflowPlan = $this->buildWorkflowPlan($tool, $userMessage, $wasToolForced);

        try {
            $arguments = $this->argumentsFor($tool, $userMessage);

            $this->recordStep('prompt', 'Loaded MCP server prompt as agent instructions.', [
                'prompt_id' => $this->promptId,
                'prompt' => $this->activePromptTitle(),
            ]);

            $this->recordStep('selection', 'Selected tool from the server based on the prompt and user order.', [
                'tool' => $tool->name,
                'available_tools' => $this->server?->tools->pluck('name')->values()->all() ?? [],
            ]);

            $this->recordStep('input', 'Mapped message to MCP tool arguments.', [
                'tool' => $tool->name,
                'arguments' => $arguments,
            ]);

            $result = app(ToolExecutor::class)->execute($tool, $arguments);

            $this->recordStep('execution', 'Tool execution completed.', [
                'tool' => $tool->name,
            ]);

            $this->messages[] = [
                'role' => 'assistant',
                'tool' => $tool->title,
                'content' => $this->formatResult($result),
            ];
        } catch (\Throwable $exception) {
            $this->error = $exception->getMessage();

            $this->messages[] = [
                'role' => 'assistant',
                'tool' => $tool->title,
                'content' => 'Error: '.$exception->getMessage(),
            ];
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectTab(string $tab): void
    {
        if (in_array($tab, ['answer', 'steps', 'tools'], true)) {
            $this->selectedTab = $tab;
        }
    }

    public function clearChat(): void
    {
        $this->messages = [];
        $this->error = null;
        $this->workflowPlan = [];
    }

    public function render()
    {
        return view('livewire.server-chat', [
            'servers' => Server::query()->where('is_active', true)->orderBy('name')->get(),
            'tools' => $this->server?->tools ?? collect(),
            'prompts' => $this->server?->prompts ?? collect(),
            'activePrompt' => $this->activePrompt(),
            'workflowPlan' => $this->workflowPlan,
        ]);
    }

    public function updatedPromptId(): void
    {
        $this->toolId = null;
    }

    private function isNovaServer(): bool
    {
        return $this->server?->slug === 'nova';
    }

    private function isMetaQuery(string $message): bool
    {
        $lower = mb_strtolower($message);
        $keywords = [
            'agente', 'agentes', 'agent', 'agents',
            'conexion', 'conexiones', 'connection', 'connections',
            'servidor', 'servidores', 'server', 'servers',
            'herramienta', 'herramientas', 'tool', 'tools',
            'disponible', 'disponibles', 'available',
            'conectado', 'conectados', 'connected',
            'mcp', 'integrac', 'integration',
            'que puedes', 'que tienes', 'que hay',
            'muestra', 'lista', 'listar', 'show', 'list',
            'capacidad', 'capacidades', 'capabilities',
            'donde puedo', 'donde se puede', 'como puedo', 'como se puede',
            'quien gestiona', 'que agente', 'que servidor',
            'estado', 'ping', 'funciona', 'accesible',
            'restaurante', 'restaurantes', 'bodega', 'bodegas',
            'taxi', 'taxis', 'traslado', 'transfer',
            'aloe', 'vinoterapia', 'lanzaloe', 'geria',
            'reservar', 'reserva', 'producto', 'productos', 'tienda',
            'hotel', 'hoteles', 'alojamiento', 'hospedaje', 'habitacion',
            'visita', 'visitas', 'tour', 'excursion', 'excursión', 'actividad',
            'ruta', 'rutas', 'itinerario',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function handleNovaMetaResponse(string $message): void
    {
        $lower = mb_strtolower($message);

        $servers = Server::query()
            ->where('is_active', true)
            ->where('slug', '!=', 'nova')
            ->withCount(['tools' => fn ($q) => $q->where('is_active', true)])
            ->withCount(['prompts' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        // Detect a specific topic to give a targeted answer
        $isRestaurant = str_contains($lower, 'restaurante') || str_contains($lower, 'mesa')
            || str_contains($lower, 'comer') || str_contains($lower, 'cenar') || str_contains($lower, 'comida');
        $isTaxi = (str_contains($lower, 'taxi') || str_contains($lower, 'traslado') || str_contains($lower, 'transfer'))
            && ! str_contains($lower, 'hotel');
        $isHotel = str_contains($lower, 'hotel') || str_contains($lower, 'hoteles')
            || str_contains($lower, 'alojamiento') || str_contains($lower, 'hospedaje') || str_contains($lower, 'habitacion');
        $isVisit = (str_contains($lower, 'visita') || str_contains($lower, 'visitas') || str_contains($lower, 'tour')
            || str_contains($lower, 'excursion') || str_contains($lower, 'excursión') || str_contains($lower, 'actividad'))
            && ! $isRestaurant;
        $isWinery = (str_contains($lower, 'bodega') || str_contains($lower, 'bodegas') || str_contains($lower, 'vino'))
            && ! $isVisit;
        $isRoute = str_contains($lower, 'ruta') || str_contains($lower, 'rutas') || str_contains($lower, 'itinerario');
        $isProduct = str_contains($lower, 'producto') || str_contains($lower, 'tienda')
            || str_contains($lower, 'aloe') || str_contains($lower, 'vinoterapia') || str_contains($lower, 'comprar');
        $isStatus = str_contains($lower, 'estado') || str_contains($lower, 'ping')
            || str_contains($lower, 'funciona') || str_contains($lower, 'accesible');

        $lines = [];

        if ($isRestaurant) {
            $sirvoMcp = Server::where('type', 'sirvo')->first();
            $branches = [];

            if (!empty($sirvoMcp?->endpoint_url)) {
                try {
                    $res = Http::withoutVerifying()->timeout(5)->get(
                        rtrim((string) $sirvoMcp->endpoint_url, '/').'/api/branches'
                    );
                    if ($res->successful()) {
                        $branches = $res->json() ?? [];
                    }
                } catch (\Throwable) {
                    // API unreachable — fall back to agent pointer
                }
            }

            if (! empty($branches)) {
                $lines[] = 'Restaurantes disponibles a través de Sirvo:';
                $lines[] = '';
                foreach (array_slice($branches, 0, 8) as $branch) {
                    $name = $branch['name'] ?? $branch['title'] ?? ($branch['id'] ?? '');
                    $address = $branch['address'] ?? $branch['location'] ?? null;
                    $line = '- **'.$name.'**';
                    if ($address) {
                        $line .= ' — '.$address;
                    }
                    $lines[] = $line;
                }
                $lines[] = '';
                $lines[] = 'Dime en cuál te interesa reservar y te gestiono la reserva (día, hora y personas).';
            } else {
                $sirvo = $servers->first(fn ($s) => str_contains(mb_strtolower($s->name), 'sirvo')
                    || str_contains(mb_strtolower($s->slug), 'sirvo'));
                $lines[] = 'Para reservar mesa puedes usar el agente **Sirvo Restaurants MCP**.';
                if ($sirvo) {
                    $lines[] = '';
                    $lines[] = '- **Agente:** '.$sirvo->name;
                    $lines[] = '- **Tools activas:** '.$sirvo->tools_count;
                }
                $lines[] = '';
                $lines[] = 'Dime para cuántas personas, qué día y a qué hora y lo gestiono yo directamente.';
            }
        } elseif ($isTaxi) {
            $taxi = $servers->filter(fn ($s) => str_contains(mb_strtolower($s->name), 'taxi')
                || str_contains(mb_strtolower($s->slug), 'taxi'));
            $lines[] = 'Para reservar un taxi tienes estos agentes disponibles:';
            $lines[] = '';
            foreach ($taxi as $s) {
                $lines[] = '- **'.$s->name.'** — '.$s->tools_count.' tools';
            }
            $lines[] = '';
            $lines[] = 'Dime origen, destino, hora y número de pasajeros y lo reservo.';
        } elseif ($isHotel) {
            $hotelMcp = $servers->first(fn ($s) => str_contains(mb_strtolower($s->name), 'hotel')
                || str_contains(mb_strtolower($s->slug), 'hotel'));
            $hotelNova = Server::where('type', 'taxilanz')->first();
            $services = $hotelNova ? $this->fetchApiItems($hotelNova->endpoint_url, '/api/hotels', ['name', 'title', 'hotel_name'], ['stars', 'category', 'location']) : [];

            if (! empty($services)) {
                $lines[] = 'Hoteles disponibles:';
                $lines[] = '';
                foreach (array_slice($services, 0, 8) as $item) {
                    $lines[] = '- **'.($item['label'] ?? '').'**'.($item['meta'] ? ' — '.$item['meta'] : '');
                }
                $lines[] = '';
                $lines[] = 'Dime en cuál te interesa y te gestiono la reserva.';
            } else {
                $lines[] = 'Para reservas de hotel usa el agente **Taxilanz Hoteles MCP**.';
                if ($hotelMcp) {
                    $lines[] = '';
                    $lines[] = '- **Agente:** '.$hotelMcp->name;
                    $lines[] = '- **Tools activas:** '.$hotelMcp->tools_count;
                }
                $lines[] = '';
                $lines[] = 'Dime nombre del hotel, fechas de entrada/salida y número de huéspedes.';
            }
        } elseif ($isVisit || $isRoute) {
            $geriaMcp = Server::where('type', 'la_geria')->first();
            $services = $geriaMcp ? $this->fetchApiItems($geriaMcp->endpoint_url, '/wp-json/lp-api/v1/services', ['title', 'name'], ['duration', 'price', 'capacity']) : [];

            if (! empty($services)) {
                $label = $isRoute ? 'Rutas y excursiones disponibles en Lanzarote' : 'Visitas y tours disponibles';
                $lines[] = $label.':';
                $lines[] = '';
                foreach (array_slice($services, 0, 8) as $item) {
                    $lines[] = '- **'.($item['label'] ?? '').'**'.($item['meta'] ? ' ('.$item['meta'].')' : '');
                }
                $lines[] = '';
                $lines[] = 'Dime cuál te interesa y te busco disponibilidad (día y personas).';
            } else {
                $geria = $servers->first(fn ($s) => str_contains(mb_strtolower($s->name), 'geria')
                    || str_contains(mb_strtolower($s->slug), 'geria'));
                $label = $isRoute ? 'Para rutas y excursiones' : 'Para visitas guiadas y tours';
                $lines[] = $label.' tienes el agente **La Geria Shop+Tours MCP**.';
                if ($geria) {
                    $lines[] = '';
                    $lines[] = '- **Agente:** '.$geria->name;
                    $lines[] = '- **Tools activas:** '.$geria->tools_count;
                }
                $lines[] = '';
                $lines[] = 'Dime qué día y cuántas personas y te busco disponibilidad.';
            }
        } elseif ($isWinery) {
            $geriaMcp = Server::where('type', 'la_geria')->first();
            $products = $geriaMcp ? $this->fetchApiItems($geriaMcp->endpoint_url, '/wp-json/wc/v3/products?per_page=6&status=publish', ['name'], ['price', 'sku']) : [];

            if (! empty($products)) {
                $lines[] = 'Vinos disponibles en La Geria:';
                $lines[] = '';
                foreach ($products as $item) {
                    $lines[] = '- **'.($item['label'] ?? '').'**'.($item['meta'] ? ' — '.$item['meta'] : '');
                }
                $lines[] = '';
                $lines[] = '¿Quieres comprar alguno o visitar la bodega?';
            } else {
                $geria = $servers->first(fn ($s) => str_contains(mb_strtolower($s->name), 'geria')
                    || str_contains(mb_strtolower($s->slug), 'geria'));
                $lines[] = 'Para bodegas y vinos tienes el agente **La Geria Shop+Tours MCP**.';
                if ($geria) {
                    $lines[] = '';
                    $lines[] = '- **Agente:** '.$geria->name;
                    $lines[] = '- **Tools activas:** '.$geria->tools_count;
                }
                $lines[] = '';
                $lines[] = 'Dime qué día y cuántas personas y te busco disponibilidad.';
            }
        } elseif ($isProduct) {
            $lanzaloe = $servers->first(fn ($s) => str_contains(mb_strtolower($s->name), 'lanzaloe')
                || str_contains(mb_strtolower($s->slug), 'lanzaloe'));
            $lines[] = 'Para productos (aloe vera, vinoterapia, vinos) tienes el agente **Lanzaloe Magento MCP**.';
            if ($lanzaloe) {
                $lines[] = '';
                $lines[] = '- **Agente:** '.$lanzaloe->name;
                $lines[] = '- **Tools activas:** '.$lanzaloe->tools_count;
            }
            $lines[] = '';
            $lines[] = '¿Quieres que busque productos concretos o te envíe el catálogo?';
        } elseif ($isStatus) {
            $lines[] = 'Estado de los agentes MCP conectados a Nova:';
            $lines[] = '';
            foreach ($servers as $server) {
                $icon = $server->is_active ? '🟢' : '🔴';
                $lines[] = $icon.' **'.$server->name.'** — '.$server->tools_count.' tools';
                $lines[] = '   `'.$server->endpoint.'`';
            }
        } else {
            // Generic: full list
            $lines[] = 'Nova tiene **'.$servers->count().' agente(s) MCP** activos:';
            $lines[] = '';
            foreach ($servers as $server) {
                $lines[] = '### '.$server->name;
                $lines[] = '- **Tools activas:** '.$server->tools_count;
                $lines[] = '- **Prompts:** '.$server->prompts_count;
                if ($server->description) {
                    $lines[] = '- '.$server->description;
                }
                $lines[] = '';
            }

            $novaPrompts = $this->server?->prompts ?? collect();
            if ($novaPrompts->isNotEmpty()) {
                $lines[] = '---';
                $lines[] = 'Prompts activos de Nova:';
                foreach ($novaPrompts as $prompt) {
                    $lines[] = '- **'.$prompt->title.'** — '.($prompt->description ?? $prompt->name);
                }
            }
        }

        $topic = match (true) {
            $isRestaurant => 'restaurant',
            $isHotel => 'hotel',
            $isVisit => 'visit',
            $isRoute => 'route',
            $isTaxi => 'taxi',
            $isWinery => 'winery',
            $isProduct => 'product',
            $isStatus => 'status',
            default => 'general',
        };

        $this->recordStep('meta', 'Nova responded to discovery/meta query from database.', [
            'servers_count' => $servers->count(),
            'topic' => $topic,
        ]);

        $this->messages[] = [
            'role' => 'assistant',
            'tool' => 'Nova',
            'content' => implode("\n", $lines),
        ];
    }

    /**
     * Fetch a list from a remote API and normalize items to {label, meta} pairs.
     *
     * @param  array<int, string>  $labelKeys  Fields to use as label (first non-empty wins)
     * @param  array<int, string>  $metaKeys  Fields to concatenate as meta info
     * @return array<int, array{label: string, meta: string}>
     */
    private function fetchApiItems(?string $baseUrl, string $path, array $labelKeys, array $metaKeys): array
    {
        if (! $baseUrl) {
            return [];
        }

        try {
            $res = Http::withoutVerifying()->timeout(5)->get(rtrim($baseUrl, '/').$path);

            if (! $res->successful()) {
                return [];
            }

            $raw = $res->json();

            // Support both root array and nested {data: [...]} / {services: [...]} etc.
            if (isset($raw['data']) && is_array($raw['data'])) {
                $raw = $raw['data'];
            } elseif (isset($raw['services']) && is_array($raw['services'])) {
                $raw = $raw['services'];
            } elseif (isset($raw['results']) && is_array($raw['results'])) {
                $raw = $raw['results'];
            }

            if (! is_array($raw) || empty($raw)) {
                return [];
            }

            return collect($raw)->map(function (mixed $item) use ($labelKeys, $metaKeys): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $label = '';
                foreach ($labelKeys as $key) {
                    if (! empty($item[$key])) {
                        $label = (string) $item[$key];
                        break;
                    }
                }

                if ($label === '') {
                    return null;
                }

                $metaParts = [];
                foreach ($metaKeys as $key) {
                    if (! empty($item[$key])) {
                        $metaParts[] = $key.': '.$item[$key];
                    }
                }

                return ['label' => $label, 'meta' => implode(', ', $metaParts)];
            })->filter()->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function argumentsFor(Tool $tool, string $message): array
    {
        $arguments = [];
        $schema = $this->normalizedSchema($tool);

        if ($schema === []) {
            return ['message' => $message];
        }

        foreach ($schema as $name => $config) {
            if (in_array($name, ['message', 'query', 'prompt', 'search', 'question', 'input'], true)) {
                $arguments[$name] = $message;

                continue;
            }

            if (array_key_exists('default', $config)) {
                $arguments[$name] = $config['default'];

                continue;
            }

            if ($config['required'] ?? false) {
                $arguments[$name] = match ($config['type'] ?? 'string') {
                    'integer', 'int' => 0,
                    'number', 'float' => 0.0,
                    'boolean', 'bool' => false,
                    'array' => [],
                    'object' => [],
                    default => $message,
                };
            }
        }

        if ($arguments === []) {
            $arguments['message'] = $message;
        }

        return $arguments;
    }

    private function selectToolFor(string $message): Tool
    {
        $tools = $this->server?->tools ?? collect();

        if ($tools->isEmpty()) {
            throw new \RuntimeException('This server has no active tools.');
        }

        if ($this->toolId) {
            $selectedTool = $tools->firstWhere('id', $this->toolId);

            if ($selectedTool) {
                return $selectedTool;
            }
        }

        return $tools
            ->sortByDesc(fn (Tool $tool): int => $this->toolScore($tool, $message))
            ->first();
    }

    private function toolScore(Tool $tool, string $message): int
    {
        $haystack = mb_strtolower(implode(' ', array_filter([
            $tool->name,
            $tool->title,
            $tool->description,
            json_encode($tool->input_schema ?? [], JSON_UNESCAPED_UNICODE),
        ])));

        $needle = mb_strtolower($message.' '.$this->promptText().' '.($this->server?->instructions ?? ''));
        $terms = array_values(array_unique(array_filter(
            preg_split('/\W+/u', $needle) ?: [],
            fn (string $term): bool => mb_strlen($term) >= 4,
        )));

        $score = 0;

        foreach ($terms as $term) {
            if (str_contains($haystack, $term)) {
                $score += 2;
            }
        }

        foreach ($this->normalizedSchema($tool) as $name => $config) {
            if (in_array($name, ['message', 'query', 'prompt', 'search', 'question', 'input'], true)) {
                $score++;
            }

            if (($config['required'] ?? false) === false) {
                $score++;
            }
        }

        return $score;
    }

    private function resolvePromptId(): ?int
    {
        if (! $this->server) {
            return null;
        }

        if ($this->promptId && $this->server->prompts->contains('id', $this->promptId)) {
            return $this->promptId;
        }

        return $this->server->prompts->first()?->id;
    }

    private function activePrompt(): ?Prompt
    {
        return $this->server?->prompts->firstWhere('id', $this->promptId);
    }

    private function activePromptTitle(): string
    {
        return $this->activePrompt()?->title ?? 'Server agent';
    }

    private function promptText(): string
    {
        $prompt = $this->activePrompt();

        if (! $prompt) {
            return '';
        }

        return collect($prompt->messages ?? [])
            ->pluck('content')
            ->filter()
            ->implode("\n\n");
    }

    private function buildWorkflowPlan(Tool $tool, string $message, bool $wasToolForced): array
    {
        return [
            'type' => 'workflow_plan',
            'strategy_type' => 'single_tool',
            'estimated_duration_seconds' => 5,
            'generated_at' => now()->format('H:i:s'),
            'original_query' => $message,
            'stages' => [
                [
                    'type' => 'single',
                    'nodes' => [
                        [
                            'tool_name' => $tool->name,
                            'tool_title' => $tool->title,
                            'input' => $message,
                            'rationale' => $this->decisionRationale($wasToolForced),
                        ],
                    ],
                ],
            ],
        ];
    }

    private function decisionRationale(bool $wasToolForced): string
    {
        if ($wasToolForced) {
            return 'Tool was manually forced in the chat controls.';
        }

        return 'Tool selected by matching the MCP prompt, server instructions, user order, tool description, and input schema.';
    }

    private function normalizedSchema(Tool $tool): array
    {
        $schema = [];

        foreach ($tool->input_schema ?? [] as $key => $config) {
            if (! is_array($config)) {
                continue;
            }

            $name = is_string($key) ? $key : ($config['name'] ?? null);

            if (! is_string($name) || $name === '') {
                continue;
            }

            $schema[$name] = $config;
        }

        return $schema;
    }

    private function formatResult(mixed $result): string
    {
        $encoded = is_string($result)
            ? $result
            : json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            return 'Unable to encode tool result.';
        }

        if (strlen($encoded) <= self::MAX_RESULT_BYTES) {
            return $encoded;
        }

        return mb_substr($encoded, 0, self::MAX_RESULT_BYTES)
            ."\n\n[Output truncated: original response was "
            .number_format(strlen($encoded) / 1024, 1)
            .' KB.]';
    }

    private function recordStep(string $type, string $description, array $data = []): void
    {
        $this->executionSteps[] = [
            'type' => $type,
            'description' => $description,
            'data' => $data,
            'time' => now()->format('H:i:s'),
        ];
    }
}
