<?php

use App\Mcp\Servers\ClickUpServer;
use App\Mcp\Servers\NovaFactuServer;
use App\Services\McpServerGenerator;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Facades\Mcp;

// Static NovaFactu MCP server for NovaFact invoicing, expenses and client portal.
Mcp::web('ia/mcp/novafactu', NovaFactuServer::class);

// Static ClickUp MCP server for task creation and listing.
Mcp::web('ia/mcp/clickup', ClickUpServer::class);

// Only register MCP servers if the table exists (prevents errors during migrations)
if (Schema::hasTable('servers')) {
    app(McpServerGenerator::class)->registerAllServers();
}
