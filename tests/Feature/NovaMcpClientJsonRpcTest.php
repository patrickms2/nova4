<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Services\Nova\NovaMcpClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NovaMcpClientJsonRpcTest extends TestCase
{
    public function test_it_lists_tools_with_json_rpc_mcp_transport(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://nova-mcp.test/mcp/la-geria-wordpress-woo-latepoint*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [
                    'tools' => [
                        ['name' => 'lageria-woo-products'],
                    ],
                ],
            ]),
        ]);

        $client = new NovaMcpClient(new Server([
            'name' => 'La Geria MCP',
            'type' => 'la_geria',
            'endpoint' => 'https://nova-mcp.test/mcp/la-geria-wordpress-woo-latepoint',
            'status' => 'active',
            'capabilities' => ['transport' => 'json_rpc'],
        ]));

        $tools = $client->listJsonRpcTools();

        $this->assertSame('lageria-woo-products', $tools[0]['name']);
        Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://nova-mcp.test/mcp/la-geria-wordpress-woo-latepoint')
            && $request['jsonrpc'] === '2.0'
            && $request['method'] === 'tools/list');
    }

    public function test_it_calls_tool_with_json_rpc_mcp_transport(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://nova-mcp.test/mcp/la-geria-wordpress-woo-latepoint*' => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [
                    'content' => [
                        ['type' => 'text', 'text' => '{"ok":true}'],
                    ],
                ],
            ]),
        ]);

        $client = new NovaMcpClient(new Server([
            'name' => 'La Geria MCP',
            'type' => 'la_geria',
            'endpoint' => 'https://nova-mcp.test/mcp/la-geria-wordpress-woo-latepoint',
            'status' => 'active',
            'capabilities' => ['transport' => 'json_rpc'],
        ]));

        $result = $client->callJsonRpcTool('lageria-woo-products', [
            'query' => ['per_page' => 8, 'status' => 'publish'],
        ]);

        $this->assertTrue($result['ok']);
        Http::assertSent(fn ($request): bool => $request['method'] === 'tools/call'
            && $request['params']['name'] === 'lageria-woo-products'
            && $request['params']['arguments']['query']['per_page'] === 8);
    }
}
