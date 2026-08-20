<?php

namespace App\Ai\Agents;

use App\Ai\Tools\NovaFactuToolAdapter;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class NovaHub implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * NovaFactu MCP tools available to this agent.
     *
     * @var array<string, class-string<\Laravel\Mcp\Server\Tool>>
     */
    private const NOVAFACTU_TOOLS = [
        'create_invoice' => \App\Mcp\Tools\NovaFactu\CreateInvoiceTool::class,
        'list_invoices' => \App\Mcp\Tools\NovaFactu\ListInvoicesTool::class,
        'list_clients' => \App\Mcp\Tools\NovaFactu\ListClientsTool::class,
        'list_concepts' => \App\Mcp\Tools\NovaFactu\ListConceptsTool::class,
        'list_companies' => \App\Mcp\Tools\NovaFactu\ListCompaniesTool::class,
        'list_expenses' => \App\Mcp\Tools\NovaFactu\ListExpensesTool::class,
        'create_expense' => \App\Mcp\Tools\NovaFactu\CreateExpenseTool::class,
        'update_expense' => \App\Mcp\Tools\NovaFactu\UpdateExpenseTool::class,
        'delete_expense' => \App\Mcp\Tools\NovaFactu\DeleteExpenseTool::class,
        'send_invoice_pdf' => \App\Mcp\Tools\NovaFactu\SendInvoicePdfTool::class,
        'create_clickup_task' => \App\Mcp\Tools\ClickUp\CreateTaskTool::class,
        'list_clickup_tasks' => \App\Mcp\Tools\ClickUp\ListTasksTool::class,
    ];

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        Eres NovaHub, el asistente de facturación de NovaFact.

        Puedes gestionar facturas, gastos, clientes, empresas y conceptos usando las herramientas disponibles.

        Reglas:
        - Usa las herramientas de listado (list_*) para explorar datos antes de crear o modificar.
        - Antes de crear, actualizar o eliminar un registro, confirma con el usuario los datos clave.
        - Para eliminar un gasto, la herramienta delete_expense requiere "confirmado: true"; pide siempre confirmación explícita al usuario primero.
        - Los importes se expresan en euros. El impuesto por defecto es IGIC 7%.
        - Responde siempre en español de forma clara y concisa.
        INSTRUCTIONS;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        foreach (self::NOVAFACTU_TOOLS as $name => $class) {
            yield new NovaFactuToolAdapter($name, app($class));
        }
    }
}
