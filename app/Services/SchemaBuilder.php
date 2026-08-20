<?php

namespace App\Services;

class SchemaBuilder
{
    public function buildInputSchema(array $parameters): array
    {
        $schema = [];

        foreach ($parameters as $param) {
            $schema[$param['name']] = [
                'type' => $param['type'] ?? 'string',
                'description' => $param['description'] ?? '',
                'required' => $param['required'] ?? false,
            ];

            if (isset($param['enum'])) {
                $schema[$param['name']]['enum'] = $param['enum'];
            }

            if (isset($param['default'])) {
                $schema[$param['name']]['default'] = $param['default'];
            }
        }

        return $schema;
    }

    public function toJsonSchema(array $schema): array
    {
        $properties = [];
        $required = [];

        foreach ($schema as $name => $config) {
            $properties[$name] = [
                'type' => $this->mapType($config['type']),
            ];

            if (! empty($config['description'])) {
                $properties[$name]['description'] = $config['description'];
            }

            if (! empty($config['enum'])) {
                $properties[$name]['enum'] = $config['enum'];
            }

            if (isset($config['default'])) {
                $properties[$name]['default'] = $config['default'];
            }

            if ($config['required'] ?? false) {
                $required[] = $name;
            }
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
        ];
    }

    protected function mapType(string $type): string
    {
        return match ($type) {
            'int', 'integer' => 'integer',
            'float', 'number' => 'number',
            'bool', 'boolean' => 'boolean',
            'array' => 'array',
            'object' => 'object',
            default => 'string',
        };
    }
}
