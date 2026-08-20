<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('NovaFactu')]
#[Version('0.1.0')]
#[Instructions('Servidor MCP de NovaFact. Expone operaciones de facturación, gastos, conceptos, clientes, empresas, formas de cobro, remesas, OCR de facturas, gastos recurrentes, el portal de cliente y gestión de proyectos, tareas y notas. Usa las herramientas de listado para explorar datos y las de acción para crear/editar/enviar. Siempre pregunta antes de crear, eliminar o enviar a VeriFactu.')]
class NovaFactuServer extends Server
{
    protected array $tools = [
        \App\Mcp\Tools\NovaFactu\CreateInvoiceTool::class,
        \App\Mcp\Tools\NovaFactu\ListInvoicesTool::class,
        \App\Mcp\Tools\NovaFactu\ListClientsTool::class,
        \App\Mcp\Tools\NovaFactu\ListNumberedClientsTool::class,
        \App\Mcp\Tools\NovaFactu\ListConceptsTool::class,
        \App\Mcp\Tools\NovaFactu\ListCompaniesTool::class,
        \App\Mcp\Tools\NovaFactu\ListExpensesTool::class,
        \App\Mcp\Tools\NovaFactu\CreateExpenseTool::class,
        \App\Mcp\Tools\NovaFactu\UpdateExpenseTool::class,
        \App\Mcp\Tools\NovaFactu\DeleteExpenseTool::class,
        \App\Mcp\Tools\NovaFactu\SendInvoicePdfTool::class,
        // Projects
        \App\Mcp\Tools\NovaFactu\ListProjectsTool::class,
        \App\Mcp\Tools\NovaFactu\CreateProjectTool::class,
        \App\Mcp\Tools\NovaFactu\UpdateProjectTool::class,
        \App\Mcp\Tools\NovaFactu\DeleteProjectTool::class,
        // Tasks
        \App\Mcp\Tools\NovaFactu\ListTasksTool::class,
        \App\Mcp\Tools\NovaFactu\CreateTaskTool::class,
        \App\Mcp\Tools\NovaFactu\UpdateTaskTool::class,
        \App\Mcp\Tools\NovaFactu\DeleteTaskTool::class,
        // Notes
        \App\Mcp\Tools\NovaFactu\ListNotesTool::class,
        \App\Mcp\Tools\NovaFactu\CreateNoteTool::class,
        \App\Mcp\Tools\NovaFactu\UpdateNoteTool::class,
        \App\Mcp\Tools\NovaFactu\DeleteNoteTool::class,
        // Categories
        \App\Mcp\Tools\NovaFactu\ListProjectCategoriesTool::class,
        \App\Mcp\Tools\NovaFactu\CreateProjectCategoryTool::class,
        \App\Mcp\Tools\NovaFactu\ListTaskCategoriesTool::class,
        \App\Mcp\Tools\NovaFactu\CreateTaskCategoryTool::class,
        // ClickUp Integration
        \App\Mcp\Tools\NovaFactu\ExportTaskToClickUpTool::class,
        \App\Mcp\Tools\NovaFactu\ImportTaskFromClickUpTool::class,
        \App\Mcp\Tools\NovaFactu\ExportNoteToClickUpTool::class,
        \App\Mcp\Tools\NovaFactu\ImportNoteFromClickUpTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
