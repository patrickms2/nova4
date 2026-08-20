<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('ClickUp')]
#[Version('0.1.0')]
#[Instructions('Servidor MCP de ClickUp para crear y listar tareas. Usa estas herramientas para gestionar tareas de proyecto desde Nova OS.')]
class ClickUpServer extends Server
{
    protected array $tools = [
        \App\Mcp\Tools\ClickUp\CreateTaskTool::class,
        \App\Mcp\Tools\ClickUp\ListTasksTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
