<?php

namespace Database\Seeders;

use App\Models\Server;
use Illuminate\Database\Seeder;

class DemoServerSeeder extends Seeder
{
    public function run(): void
    {
        $server = Server::create([
            'name' => 'Demo Utilities Server',
            'slug' => 'demo-utilities',
            'description' => 'A demo server with common utility tools',
            'version' => '1.0.0',
            'instructions' => 'This server provides utility tools for text processing, calculations, and data formatting.',
            'transport' => 'web',
            'is_active' => true,
        ]);

        // Calculator Tool
        $server->tools()->create([
            'name' => 'calculate',
            'title' => 'Calculator',
            'description' => 'Performs basic mathematical calculations',
            'input_schema' => [
                'expression' => [
                    'type' => 'string',
                    'description' => 'Mathematical expression to evaluate (e.g., "2 + 2 * 3")',
                    'required' => true,
                ],
            ],
            'handler_code' => '
$expr = $input["expression"];
// Only allow safe math operations
$expr = preg_replace("/[^0-9+\-*\/().\ ]/", "", $expr);
try {
    $result = eval("return {$expr};");
    return ["_structured" => true, "expression" => $input["expression"], "result" => $result];
} catch (\Throwable $e) {
    return "Error: Invalid expression";
}',
            'output_schema' => [
                'expression' => ['type' => 'string', 'required' => true],
                'result' => ['type' => 'number', 'required' => true],
            ],
            'is_active' => true,
        ]);

        // Text Transform Tool
        $server->tools()->create([
            'name' => 'text-transform',
            'title' => 'Text Transform',
            'description' => 'Transforms text in various ways',
            'input_schema' => [
                'text' => [
                    'type' => 'string',
                    'description' => 'The text to transform',
                    'required' => true,
                ],
                'operation' => [
                    'type' => 'string',
                    'description' => 'The transformation to apply',
                    'enum' => ['uppercase', 'lowercase', 'reverse', 'wordcount'],
                    'required' => true,
                ],
            ],
            'handler_code' => '
$text = $input["text"];
$op = $input["operation"];

return match($op) {
    "uppercase" => strtoupper($text),
    "lowercase" => strtolower($text),
    "reverse" => strrev($text),
    "wordcount" => "Word count: " . str_word_count($text),
    default => "Unknown operation"
};',
            'annotations' => ['isReadOnly' => true, 'isIdempotent' => true],
            'is_active' => true,
        ]);

        // JSON Format Tool
        $server->tools()->create([
            'name' => 'json-format',
            'title' => 'JSON Formatter',
            'description' => 'Formats and validates JSON data',
            'input_schema' => [
                'json' => [
                    'type' => 'string',
                    'description' => 'JSON string to format',
                    'required' => true,
                ],
            ],
            'handler_code' => '
$json = $input["json"];
$decoded = json_decode($json);

if (json_last_error() !== JSON_ERROR_NONE) {
    return "Invalid JSON: " . json_last_error_msg();
}

return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);',
            'annotations' => ['isReadOnly' => true],
            'is_active' => true,
        ]);

        // Static Resource
        $server->resources()->create([
            'name' => 'server-docs',
            'title' => 'Server Documentation',
            'description' => 'Documentation for using this demo server',
            'uri' => 'demo://docs/readme',
            'mime_type' => 'text/markdown',
            'content' => '# Demo Utilities Server

## Available Tools

### calculate
Evaluates mathematical expressions.

### text-transform
Transforms text (uppercase, lowercase, reverse, wordcount).

### json-format
Formats and validates JSON strings.

## Usage
Call any tool with the required parameters as specified in their schemas.',
            'is_active' => true,
        ]);

        // Prompt
        $server->prompts()->create([
            'name' => 'explain-code',
            'title' => 'Code Explainer',
            'description' => 'Generates a prompt to explain code snippets',
            'arguments' => [
                ['name' => 'language', 'description' => 'Programming language', 'required' => true],
                ['name' => 'level', 'description' => 'Explanation level (beginner/intermediate/expert)', 'required' => false],
            ],
            'messages' => [
                [
                    'role' => 'assistant',
                    'content' => 'You are a helpful programming tutor specializing in {language}. Explain code at a {level} level.',
                ],
                [
                    'role' => 'user',
                    'content' => 'Please explain the following {language} code:',
                ],
            ],
            'is_active' => true,
        ]);
    }
}
