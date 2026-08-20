<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MCP\TaxilanzMCPServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MCPController extends Controller
{
    private TaxilanzMCPServer $mcpServer;

    public function __construct(TaxilanzMCPServer $mcpServer)
    {
        $this->mcpServer = $mcpServer;
    }

    /**
     * List all available MCP tools
     */
    public function listTools(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'tools' => $this->mcpServer->getTools(),
            ],
        ]);
    }

    /**
     * List all available MCP tools
     */
    public function listHotels(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = max(1, min(500, (int) $request->integer('per_page', 100)));

        return response()->json([
            'success' => true,
            'data' => [
                'tools' => $this->mcpServer->getHotels($page, $perPage),
            ],
        ]);
    }

    /**
     * List all available MCP tools
     */
    public function listServicios(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'tools' => $this->mcpServer->getServicios(),
            ],
        ]);
    }

    /**
     * Execute a specific MCP tool
     */
    public function executeTool(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'arguments' => 'sometimes|array',
        ]);

        $toolName = $request->input('name');
        $arguments = $request->input('arguments', []);

        $result = $this->mcpServer->executeTool($toolName, $arguments);

        return response()->json($result);
    }

    /**
     * Get MCP server information
     */
    public function serverInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'name' => 'Taxilanz MCP Server',
                'version' => '1.0.0',
                'description' => 'MCP server for Taxilanz taxi management system with 180+ hotels',
                'capabilities' => [
                    'tools' => true,
                    'resources' => false,
                    'prompts' => false,
                ],
                'tools_count' => count($this->mcpServer->getTools()),
            ],
        ]);
    }
}
