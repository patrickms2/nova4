<?php

namespace App\Livewire;

use App\Models\Tool;
use App\Services\ToolExecutor;
use Livewire\Component;

class ToolTester extends Component
{
    private const MAX_RESULT_BYTES = 250000;

    public ?int $toolId = null;

    public ?Tool $tool = null;

    public array $inputValues = [];

    public mixed $result = null;

    public ?string $error = null;

    public bool $isLoading = false;

    public ?float $executionTime = null;

    public function mount(?int $toolId = null): void
    {
        if ($toolId) {
            $this->toolId = $toolId;
            $this->loadTool();
        }
    }

    public function loadTool(): void
    {
        $this->tool = Tool::with('server')->find($this->toolId);

        if ($this->tool) {
            $this->initializeInputs();
        }
    }

    protected function initializeInputs(): void
    {
        $this->inputValues = [];
        foreach ($this->normalizedSchema() as $name => $config) {
            $this->inputValues[$name] = $config['default'] ?? '';
        }
    }

    public function execute(): void
    {
        $this->isLoading = true;
        $this->error = null;
        $this->result = null;

        try {
            $startTime = microtime(true);

            $executor = app(ToolExecutor::class);
            $this->result = $this->formatResultForDisplay(
                $executor->execute($this->tool, $this->inputValues),
            );

            $this->executionTime = (microtime(true) - $startTime) * 1000;
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('livewire.tool-tester', [
            'tools' => Tool::with('server')
                ->where('is_active', true)
                ->get(),
            'inputSchema' => $this->tool ? $this->normalizedSchema() : [],
        ]);
    }

    private function normalizedSchema(): array
    {
        $schema = [];

        foreach ($this->tool->input_schema ?? [] as $key => $config) {
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

    private function formatResultForDisplay(mixed $result): string|array
    {
        $encoded = is_string($result)
            ? $result
            : json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            return 'Unable to encode tool result.';
        }

        if (strlen($encoded) <= self::MAX_RESULT_BYTES) {
            $decoded = json_decode($encoded, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $encoded;
        }

        return mb_substr($encoded, 0, self::MAX_RESULT_BYTES)
            ."\n\n[Output truncated in MCP Studio: original response was "
            .number_format(strlen($encoded) / 1024, 1)
            .' KB. Narrow the query parameters to inspect the full remote data.]';
    }
}
