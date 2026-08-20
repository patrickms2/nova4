<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Tool;
use Illuminate\Support\Collection;

class PromptlyAgentMcpPresetCatalog
{
    public function servers(): array
    {
        return [
            'promptly-agent' => [
                'name' => 'PromptlyAgent Agent Server',
                'slug' => 'promptly-agent',
                'description' => 'MCP server inspired by PromptlyAgent agent management and execution operations.',
                'version' => '1.0.0',
                'instructions' => <<<'TEXT'
This server exposes Nova-compatible PromptlyAgent-style agent operations.

Available operations:
- List and inspect Nova AI profiles
- Execute an AI profile with a message

The tools are adapted to Nova MCP dynamic tools and use Nova models/services instead of PromptlyAgent Prism internals.
TEXT,
                'transport' => 'web',
                'endpoint' => '/mcp/promptly-agent',
                'middleware' => ['auth:sanctum', 'throttle:100,1'],
                'metadata' => [
                    'source' => 'promptlyagent',
                    'integration' => 'agent',
                ],
                'tools' => ['list_agents', 'get_agent_details', 'invoke_agent'],
            ],
            'promptly-knowledge' => [
                'name' => 'PromptlyAgent Knowledge Server',
                'slug' => 'promptly-knowledge',
                'description' => 'MCP server inspired by PromptlyAgent knowledge search and RAG operations.',
                'version' => '1.0.0',
                'instructions' => <<<'TEXT'
This server exposes Nova-compatible PromptlyAgent-style knowledge operations.

Available operations:
- Search Nova AI knowledge
- Retrieve RAG context from Nova AI knowledge
- List knowledge fragments

The tools are adapted to NovaAiKnowledge and NovaKnowledgeService.
TEXT,
                'transport' => 'web',
                'endpoint' => '/mcp/promptly-knowledge',
                'middleware' => ['auth:sanctum', 'throttle:100,1'],
                'metadata' => [
                    'source' => 'promptlyagent',
                    'integration' => 'knowledge',
                ],
                'tools' => ['search_knowledge', 'query_knowledge_rag', 'list_knowledge_documents'],
            ],
        ];
    }

    public function tools(): array
    {
        return [
            'list_agents' => [
                'name' => 'list_agents',
                'title' => 'List Agents',
                'description' => 'List active Nova AI profiles with their business, provider, model, and status.',
                'input_schema' => [],
                'handler_code' => <<<'PHP'
$profiles = \App\Models\NovaAiProfile::query()
    ->with('business:id,name')
    ->latest()
    ->limit(100)
    ->get()
    ->map(fn (\App\Models\NovaAiProfile $profile): array => [
        'id' => $profile->id,
        'name' => $profile->name,
        'business' => $profile->business?->name,
        'provider' => $profile->provider,
        'model' => $profile->model,
        'status' => $profile->status,
        'temperature' => $profile->temperature,
        'max_tokens' => $profile->max_tokens,
    ])
    ->values()
    ->all();

return ['_structured' => true, 'agents' => $profiles];
PHP,
                'annotations' => ['isReadOnly' => true, 'isIdempotent' => true, 'isDestructive' => false, 'isOpenWorld' => false],
            ],
            'get_agent_details' => [
                'name' => 'get_agent_details',
                'title' => 'Get Agent Details',
                'description' => 'Get a Nova AI profile configuration and assigned knowledge count.',
                'input_schema' => [
                    ['name' => 'agent_id', 'type' => 'integer', 'description' => 'The Nova AI profile ID to retrieve', 'required' => true],
                ],
                'handler_code' => <<<'PHP'
$agentId = (int) ($input['agent_id'] ?? 0);
$profile = \App\Models\NovaAiProfile::query()
    ->with('business:id,name')
    ->withCount('knowledge')
    ->find($agentId);

if (! $profile) {
    return ['_structured' => true, 'error' => 'Agent not found'];
}

return [
    '_structured' => true,
    'agent' => [
        'id' => $profile->id,
        'name' => $profile->name,
        'business' => $profile->business?->name,
        'provider' => $profile->provider,
        'model' => $profile->model,
        'status' => $profile->status,
        'system_prompt' => $profile->system_prompt,
        'temperature' => $profile->temperature,
        'max_tokens' => $profile->max_tokens,
        'tools_policy' => $profile->tools_policy,
        'settings' => $profile->settings,
        'knowledge_count' => $profile->knowledge_count,
    ],
];
PHP,
                'annotations' => ['isReadOnly' => true, 'isIdempotent' => true, 'isDestructive' => false, 'isOpenWorld' => false],
            ],
            'invoke_agent' => [
                'name' => 'invoke_agent',
                'title' => 'Invoke Agent',
                'description' => 'Execute NovaAiService with a message and optional context. This is a Nova-compatible PromptlyAgent invoke preset.',
                'input_schema' => [
                    ['name' => 'message', 'type' => 'string', 'description' => 'Message or question to send to Nova', 'required' => true],
                    ['name' => 'context', 'type' => 'object', 'description' => 'Optional execution context', 'required' => false],
                ],
                'handler_code' => <<<'PHP'
$message = trim((string) ($input['message'] ?? ''));
$context = is_array($input['context'] ?? null) ? $input['context'] : null;

if ($message === '') {
    return ['_structured' => true, 'error' => 'Message is required'];
}

$service = app(\App\Services\Nova\NovaAiService::class);
$response = $service->generateResponse($message, [['role' => 'user', 'content' => $message]], $context);

return ['_structured' => true, 'response' => $response];
PHP,
                'annotations' => ['isReadOnly' => false, 'isIdempotent' => false, 'isDestructive' => false, 'isOpenWorld' => true],
            ],
            'search_knowledge' => [
                'name' => 'search_knowledge',
                'title' => 'Search Knowledge',
                'description' => 'Search Nova AI knowledge fragments with PromptlyAgent-style query and limit parameters.',
                'input_schema' => [
                    ['name' => 'query', 'type' => 'string', 'description' => 'Search query text', 'required' => true],
                    ['name' => 'limit', 'type' => 'integer', 'description' => 'Maximum results to return', 'required' => false, 'default' => 5],
                    ['name' => 'business_id', 'type' => 'integer', 'description' => 'Optional Nova business ID filter', 'required' => false],
                ],
                'handler_code' => <<<'PHP'
$query = trim((string) ($input['query'] ?? ''));
$limit = max(1, min(20, (int) ($input['limit'] ?? 5)));
$businessId = isset($input['business_id']) ? (int) $input['business_id'] : null;

if ($query === '') {
    return ['_structured' => true, 'error' => 'Query is required'];
}

$terms = array_values(array_filter(preg_split('/\W+/u', mb_strtolower($query)) ?: [], fn (string $term): bool => mb_strlen($term) >= 4));

$items = \App\Models\NovaAiKnowledge::query()
    ->when($businessId, fn ($builder) => $builder->where('nova_business_id', $businessId))
    ->where('status', 'active')
    ->latest()
    ->limit(100)
    ->get(['id', 'nova_business_id', 'title', 'content', 'metadata'])
    ->map(function (\App\Models\NovaAiKnowledge $knowledge) use ($terms): array {
        $content = mb_strtolower($knowledge->title.' '.$knowledge->content);
        $score = 0;

        foreach ($terms as $term) {
            if (str_contains($content, $term)) {
                $score++;
            }
        }

        return [
            'id' => $knowledge->id,
            'business_id' => $knowledge->nova_business_id,
            'title' => $knowledge->title,
            'summary' => mb_substr(strip_tags($knowledge->content), 0, 240),
            'score' => $score,
            'metadata' => $knowledge->metadata,
        ];
    })
    ->sortByDesc('score')
    ->take($limit)
    ->values()
    ->all();

return ['_structured' => true, 'query' => $query, 'results' => $items];
PHP,
                'annotations' => ['isReadOnly' => true, 'isIdempotent' => true, 'isDestructive' => false, 'isOpenWorld' => false],
            ],
            'query_knowledge_rag' => [
                'name' => 'query_knowledge_rag',
                'title' => 'Query Knowledge RAG',
                'description' => 'Retrieve Nova knowledge context suitable for AI generation.',
                'input_schema' => [
                    ['name' => 'query', 'type' => 'string', 'description' => 'Query to retrieve relevant context', 'required' => true],
                    ['name' => 'limit', 'type' => 'integer', 'description' => 'Maximum knowledge fragments', 'required' => false, 'default' => 5],
                    ['name' => 'business_id', 'type' => 'integer', 'description' => 'Optional Nova business ID filter', 'required' => false],
                ],
                'handler_code' => <<<'PHP'
$query = trim((string) ($input['query'] ?? ''));
$limit = max(1, min(20, (int) ($input['limit'] ?? 5)));
$businessId = isset($input['business_id']) ? (int) $input['business_id'] : null;

if ($query === '') {
    return ['_structured' => true, 'error' => 'Query is required'];
}

$business = $businessId ? \App\Models\NovaBusiness::find($businessId) : null;
$knowledge = app(\App\Services\Nova\NovaKnowledgeService::class)->relevantKnowledge($business, $query, $limit);
$context = collect($knowledge)
    ->map(fn (array $item): string => "# {$item['title']}\n{$item['content']}")
    ->implode("\n\n");

return ['_structured' => true, 'query' => $query, 'context' => $context, 'sources' => $knowledge];
PHP,
                'annotations' => ['isReadOnly' => true, 'isIdempotent' => true, 'isDestructive' => false, 'isOpenWorld' => false],
            ],
            'list_knowledge_documents' => [
                'name' => 'list_knowledge_documents',
                'title' => 'List Knowledge Documents',
                'description' => 'List Nova AI knowledge fragments with optional status and business filters.',
                'input_schema' => [
                    ['name' => 'limit', 'type' => 'integer', 'description' => 'Maximum documents to return', 'required' => false, 'default' => 20],
                    ['name' => 'status', 'type' => 'string', 'description' => 'Optional status filter', 'required' => false],
                    ['name' => 'business_id', 'type' => 'integer', 'description' => 'Optional Nova business ID filter', 'required' => false],
                ],
                'handler_code' => <<<'PHP'
$limit = max(1, min(100, (int) ($input['limit'] ?? 20)));
$status = $input['status'] ?? null;
$businessId = isset($input['business_id']) ? (int) $input['business_id'] : null;

$documents = \App\Models\NovaAiKnowledge::query()
    ->when($businessId, fn ($builder) => $builder->where('nova_business_id', $businessId))
    ->when($status, fn ($builder) => $builder->where('status', $status))
    ->latest()
    ->limit($limit)
    ->get(['id', 'nova_business_id', 'nova_ai_profile_id', 'title', 'status', 'metadata', 'updated_at'])
    ->map(fn (\App\Models\NovaAiKnowledge $knowledge): array => [
        'id' => $knowledge->id,
        'business_id' => $knowledge->nova_business_id,
        'agent_id' => $knowledge->nova_ai_profile_id,
        'title' => $knowledge->title,
        'status' => $knowledge->status,
        'metadata' => $knowledge->metadata,
        'updated_at' => $knowledge->updated_at?->toISOString(),
    ])
    ->values()
    ->all();

return ['_structured' => true, 'documents' => $documents];
PHP,
                'annotations' => ['isReadOnly' => true, 'isIdempotent' => true, 'isDestructive' => false, 'isOpenWorld' => false],
            ],
        ];
    }

    public function installServer(string $serverKey): Collection
    {
        $serverPreset = $this->serverPreset($serverKey);

        $server = Server::query()->updateOrCreate(
            ['slug' => $serverPreset['slug']],
            collect($serverPreset)
                ->except('tools')
                ->merge(['is_active' => true])
                ->all(),
        );

        return collect($serverPreset['tools'])
            ->map(fn (string $toolKey): Tool => $this->installTool($server, $toolKey));
    }

    public function installTool(Server $server, string $toolKey): Tool
    {
        $toolPreset = $this->toolPreset($toolKey);

        return $server->tools()->updateOrCreate(
            ['name' => $toolPreset['name']],
            collect($toolPreset)
                ->merge([
                    'metadata' => [
                        'source' => 'promptlyagent',
                        'preset' => $toolKey,
                    ],
                    'is_active' => true,
                    'sort_order' => $this->sortOrderFor($toolKey),
                ])
                ->all(),
        );
    }

    public function serverPreset(string $serverKey): array
    {
        return $this->servers()[$serverKey] ?? throw new \InvalidArgumentException("Unknown PromptlyAgent server preset [{$serverKey}].");
    }

    public function toolPreset(string $toolKey): array
    {
        return $this->tools()[$toolKey] ?? throw new \InvalidArgumentException("Unknown PromptlyAgent tool preset [{$toolKey}].");
    }

    public function serverOptions(): array
    {
        return collect($this->servers())
            ->mapWithKeys(fn (array $server, string $key): array => [$key => $server['name']])
            ->all();
    }

    public function toolOptions(): array
    {
        return collect($this->tools())
            ->mapWithKeys(fn (array $tool, string $key): array => [$key => $tool['title']])
            ->all();
    }

    private function sortOrderFor(string $toolKey): int
    {
        return array_search($toolKey, array_keys($this->tools()), true) ?: 0;
    }
}
