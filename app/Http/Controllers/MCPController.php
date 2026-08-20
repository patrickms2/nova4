<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Server as ServerModel;
use App\Models\Taxi\Hotel;
use App\Services\MCP\TaxilanzMCPServer;
use App\Services\Nova\NovaMcpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Mcp\DynamicServer;
use App\Services\McpServerGenerator;

class MCPController extends Controller
{

    private ?ServerModel $serverModel;

    /**
     * NovaFactu MCP tools exposed through this controller.
     *
     * @var array<string, class-string<\Laravel\Mcp\Server\Tool>>
     */
    private const NOVAFACTU_TOOLS = [
        'create-invoice-tool' => \App\Mcp\Tools\NovaFactu\CreateInvoiceTool::class,
        'list-invoices-tool' => \App\Mcp\Tools\NovaFactu\ListInvoicesTool::class,
        'list-clients-tool' => \App\Mcp\Tools\NovaFactu\ListClientsTool::class,
        'list-concepts-tool' => \App\Mcp\Tools\NovaFactu\ListConceptsTool::class,
        'list-companies-tool' => \App\Mcp\Tools\NovaFactu\ListCompaniesTool::class,
        'list-expenses-tool' => \App\Mcp\Tools\NovaFactu\ListExpensesTool::class,
        'create-expense-tool' => \App\Mcp\Tools\NovaFactu\CreateExpenseTool::class,
        'update-expense-tool' => \App\Mcp\Tools\NovaFactu\UpdateExpenseTool::class,
        'delete-expense-tool' => \App\Mcp\Tools\NovaFactu\DeleteExpenseTool::class,
        'send-invoice-pdf-tool' => \App\Mcp\Tools\NovaFactu\SendInvoicePdfTool::class,

        // Casa El Patio / Property OS
        'casa-list-reservations' => \App\Mcp\Tools\CasaElPatio\ListReservationsTool::class,
        'casa-list-expenses' => \App\Mcp\Tools\CasaElPatio\ListExpensesTool::class,
        'casa-list-tasks' => \App\Mcp\Tools\CasaElPatio\ListTasksTool::class,
        'casa-list-incidents' => \App\Mcp\Tools\CasaElPatio\ListIncidentsTool::class,
        'casa-monthly-reservations-report' => \App\Mcp\Tools\CasaElPatio\MonthlyReservationsReportTool::class,
    ];


    public function __construct(ServerModel $server)
    {
        $this->serverModel = $server->query()
            ->with(['tools:id,name,title,server_id'])
            ->where('is_active', true)
            ->where('slug', '=', 'nova')
            ->withCount(['tools' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->first();
    }

    /**
     * List all available MCP tools
     */
    public function listTools(): JsonResponse
    {
        $tools = $this->serverModel->tools()->get();

        $toolsData = $tools->map(function ($tool) {
            return [
                'name' => $tool->name,
                'description' => $tool->title,
            ];
        });

        $novaFactuTools = collect(self::NOVAFACTU_TOOLS)->map(function (string $class, string $name) {
            $tool = app($class);

            return [
                'name' => $name,
                'description' => $tool->description(),
            ];
        })->values();

        $toolsData = $toolsData->concat($novaFactuTools);

             return response()->json([
            'success' => true,
            'data' => [
                'name' => 'Nova MCP tools',
                'version' => '1.0.0',
                'tools' => $toolsData->toArray(),
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

        if (isset(self::NOVAFACTU_TOOLS[$toolName])) {
            return $this->executeNovaFactuTool(self::NOVAFACTU_TOOLS[$toolName], $arguments);
        }

        $result = $this->mcpServer->executeTool($toolName, $arguments);

        return response()->json($result);
    }

    /**
     * Execute a NovaFactu MCP tool class directly.
     *
     * @param  class-string<\Laravel\Mcp\Server\Tool>  $class
     * @param  array<string, mixed>  $arguments
     */
    private function executeNovaFactuTool(string $class, array $arguments): JsonResponse
    {
        try {
            $tool = app($class);
            $response = $tool->handle(new \Laravel\Mcp\Request($arguments));

            $content = (string) $response->content();
            $decoded = json_decode($content, true);

            return response()->json([
                'success' => ! $response->isError(),
                'data' => $decoded ?? $content,
            ], $response->isError() ? 422 : 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get MCP server information
     */
    public function serverInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'name' => 'Nova MCP Server',
                'version' => '1.0.0',
                'description' => 'MCP server for Nova MCP management system - Facturación, Gastos, Ingresos, etc.',
                'capabilities' => [
                    'tools' => true,
                    'resources' => false,
                    'prompts' => false,
                ],
                'tools_count' => count($this->serverModel->tools()->get()),
            ],
        ]);
    }
}
