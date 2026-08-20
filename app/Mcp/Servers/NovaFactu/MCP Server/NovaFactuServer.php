<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('NovaFactu')]
#[Version('0.1.0')]
#[Instructions('Servidor MCP de NovaFact. Expone operaciones de facturación, gastos, conceptos, clientes, empresas, formas de cobro, remesas, OCR de facturas, gastos recurrentes y el portal de cliente. Usa las herramientas de listado para explorar datos y las de acción para crear/editar/enviar. Siempre pregunta antes de crear, eliminar o enviar a VeriFactu.')]
class NovaFactuServer extends Server
{
    protected array $tools = [
        \App\Mcp\Tools\NovaFactu\CreateInvoiceTool::class,
        \App\Mcp\Tools\NovaFactu\ListInvoicesTool::class,
        \App\Mcp\Tools\NovaFactu\ListClientsTool::class,
        \App\Mcp\Tools\NovaFactu\ListConceptsTool::class,
        \App\Mcp\Tools\NovaFactu\ListCompaniesTool::class,
        \App\Mcp\Tools\NovaFactu\ListExpensesTool::class,
        \App\Mcp\Tools\NovaFactu\CreateExpenseTool::class,
        \App\Mcp\Tools\NovaFactu\UpdateExpenseTool::class,
        \App\Mcp\Tools\NovaFactu\DeleteExpenseTool::class,
        \App\Mcp\Tools\NovaFactu\SendInvoicePdfTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
