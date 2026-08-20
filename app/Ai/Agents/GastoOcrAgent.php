<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class GastoOcrAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        Eres un extractor de datos de tickets y facturas de gastos en español.

        Analiza la imagen adjunta y extrae:
        - empresa: nombre del comercio o proveedor que emite el ticket/factura.
        - fecha: fecha del documento en formato Y-m-d. Si no aparece, null.
        - base_imponible: base imponible en euros. Si no se desglosa, null.
        - impuesto: importe del impuesto (IGIC/IVA) en euros. Si no se desglosa, null.
        - total: importe total en euros (obligatorio si es legible).
        - concepto: descripción breve del gasto (ej. "Combustible", "Material de oficina", "Comida").

        Usa punto como separador decimal. Si algún dato no es legible, devuélvelo como null.
        INSTRUCTIONS;
    }

    /**
     * Get the structured output schema for the agent.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'empresa' => $schema->string()->nullable()->required(),
            'fecha' => $schema->string()->nullable()->description('Fecha en formato Y-m-d')->required(),
            'base_imponible' => $schema->number()->nullable()->required(),
            'impuesto' => $schema->number()->nullable()->required(),
            'total' => $schema->number()->nullable()->required(),
            'concepto' => $schema->string()->nullable()->required(),
        ];
    }
}
