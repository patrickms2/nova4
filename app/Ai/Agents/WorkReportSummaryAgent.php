<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class WorkReportSummaryAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        Eres un asistente que resume partes de trabajo diarios para profesionales de mantenimiento
        (jardinería, limpieza, mantenimiento de propiedades vacacionales, etc).

        Recibirás la transcripción literal de un mensaje de voz en español describiendo el trabajo
        realizado. Genera un resumen breve, claro y profesional en tercera persona, en 1-3 frases,
        conservando los datos concretos mencionados (zonas, tareas, incidencias).

        No inventes información que no esté en la transcripción.
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
            'summary' => $schema->string()->required(),
        ];
    }
}
