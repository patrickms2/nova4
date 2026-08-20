<?php

namespace App\Mcp;

use App\Models\McpLog;
use App\Models\Tool as ToolModel;
use App\Services\ToolExecutor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DynamicTool extends Tool
{
    protected ToolModel $toolModel;

    public function __construct(ToolModel $toolModel)
    {
        $this->toolModel = $toolModel;
        $this->name = $toolModel->name;
        $this->title = $toolModel->title;
        $this->description = $toolModel->description;
    }

    public function handle(Request $request): Response
    {
        $startTime = microtime(true);

        try {
            $executor = app(ToolExecutor::class);
            $result = $executor->execute(
                $this->toolModel,
                $request->all()
            );

            $this->logExecution($request, $result, $startTime);

            if (is_array($result) && isset($result['_structured'])) {
                unset($result['_structured']);

                return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }

            return Response::text((string) $result);
        } catch (\Throwable $e) {
            $this->logError($request, $e, $startTime);

            return Response::error($e->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->buildSchema($schema, $this->toolModel->input_schema ?? []);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        if (empty($this->toolModel->output_schema)) {
            return [];
        }

        return $this->buildSchema($schema, $this->toolModel->output_schema);
    }

    protected function buildSchema(JsonSchema $schema, array $definition): array
    {
        $result = [];
        foreach ($definition as $key => $config) {
            if (! is_array($config)) {
                continue;
            }

            $name = is_string($key) ? $key : ($config['name'] ?? null);

            if (! is_string($name) || $name === '') {
                continue;
            }

            $type = $config['type'] ?? 'string';
            $field = match ($type) {
                'string' => $schema->string(),
                'integer', 'int' => $schema->integer(),
                'number', 'float' => $schema->number(),
                'boolean', 'bool' => $schema->boolean(),
                'array' => $schema->array(),
                'object' => $schema->object(),
                default => $schema->string(),
            };

            if (! empty($config['description'])) {
                $field->description($config['description']);
            }
            if (! empty($config['enum'])) {
                $field->enum($config['enum']);
            }
            if (isset($config['default'])) {
                $field->default($config['default']);
            }
            if ($config['required'] ?? false) {
                $field->required();
            }

            $result[$name] = $field;
        }

        return $result;
    }

    protected function logExecution(Request $request, mixed $result, float $startTime): void
    {
        McpLog::create([
            'server_id' => $this->toolModel->server_id,
            'tool_id' => $this->toolModel->id,
            'type' => 'request',
            'method' => 'tools/call',
            'request_data' => $request->all(),
            'response_data' => is_array($result) ? $result : ['text' => $result],
            'duration_ms' => (int) ((microtime(true) - $startTime) * 1000),
            'ip_address' => request()->ip(),
        ]);
    }

    protected function logError(Request $request, \Throwable $e, float $startTime): void
    {
        McpLog::create([
            'server_id' => $this->toolModel->server_id,
            'tool_id' => $this->toolModel->id,
            'type' => 'error',
            'method' => 'tools/call',
            'request_data' => $request->all(),
            'error_message' => $e->getMessage(),
            'duration_ms' => (int) ((microtime(true) - $startTime) * 1000),
            'ip_address' => request()->ip(),
        ]);
    }
}
