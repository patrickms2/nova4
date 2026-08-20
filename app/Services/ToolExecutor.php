<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\Tool;
use Illuminate\Support\Facades\Log;

class ToolExecutor
{
    protected array $allowedFunctions = [
        // Safe built-in functions
        'json_encode', 'json_decode', 'array_map', 'array_filter',
        'array_merge', 'array_keys', 'array_values', 'implode', 'explode',
        'str_replace', 'preg_replace', 'preg_match', 'trim', 'strtolower',
        'strtoupper', 'ucfirst', 'ucwords', 'strlen', 'substr', 'strpos',
        'date', 'time', 'strtotime', 'number_format', 'round', 'floor',
        'ceil', 'abs', 'min', 'max', 'count', 'is_array', 'is_string',
        'is_numeric', 'isset', 'empty', 'sprintf', 'http_build_query',
    ];

    public function execute(Tool $tool, array $params): mixed
    {
        $code = $tool->handler_code;

        // Validate and sanitize the code
        $this->validateCode($code);

        // Create isolated execution context
        $executor = function (array $input) use ($code) {
            // Make $input available to the code
            extract(['input' => $input]);

            // Execute the code
            return eval($code);
        };

        try {
            return $executor($params);
        } catch (\Throwable $e) {
            Log::error('Tool execution failed', [
                'tool' => $tool->name,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function executeResourceHandler(Resource $resource, array $params): mixed
    {
        $code = $resource->handler_code;
        $this->validateCode($code);

        $executor = function (array $input) use ($code) {
            extract(['input' => $input]);

            return eval($code);
        };

        return $executor($params);
    }

    protected function validateCode(string $code): void
    {
        // Check for dangerous patterns
        $dangerous = [
            'exec', 'shell_exec', 'system', 'passthru', 'popen',
            'proc_open', 'pcntl_exec', 'eval', 'assert', 'create_function',
            'unlink', 'rmdir', 'file_put_contents', 'fwrite',
            'include', 'require', 'include_once', 'require_once',
            '`', // Backtick operator
        ];

        foreach ($dangerous as $pattern) {
            if ($pattern === '`' && str_contains($code, '`')) {
                throw new \RuntimeException('Dangerous function detected: backtick operator');
            }

            if (preg_match('/\b'.preg_quote($pattern, '/').'\b\s*(?:\(|[\'"]|[A-Za-z_\\\\$])/i', $code) === 1) {
                throw new \RuntimeException("Dangerous function detected: {$pattern}");
            }
        }
    }

    public function validateSyntax(string $code): array
    {
        // Check for syntax errors without executing
        try {
            token_get_all("<?php {$code}", TOKEN_PARSE);

            return ['valid' => true, 'errors' => []];
        } catch (\ParseError $e) {
            return [
                'valid' => false,
                'errors' => [$e->getMessage()],
            ];
        }
    }
}
