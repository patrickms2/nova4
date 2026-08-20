<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Server\Tool as McpTool;
use Stringable;
use Throwable;

class NovaFactuToolAdapter implements Tool
{
    public function __construct(
        protected string $name,
        protected McpTool $tool,
    ) {}

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return $this->tool->description();
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        try {
            $response = $this->tool->handle(new McpRequest($request->toArray()));

            return (string) $response->content();
        } catch (ValidationException $e) {
            return json_encode(['error' => 'Datos inválidos', 'detalles' => $e->errors()]);
        } catch (Throwable $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get the tool's schema definition.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->tool->schema($schema);
    }
}
