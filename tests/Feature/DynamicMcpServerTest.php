<?php

namespace Tests\Feature;

use App\Mcp\DynamicServer;
use App\Models\Server;
use App\Models\Tool;
use App\Services\PromptlyAgentMcpPresetCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Tests\TestCase;

class DynamicMcpServerTest extends TestCase
{
    public function test_dynamic_server_starts_with_injected_transport(): void
    {
        $this->createMcpTables();

        $server = new DynamicServer(new FakeTransporter);

        $server->start();

        $this->assertTrue(true);
    }

    public function test_dynamic_server_hydrates_database_tools_for_mcp_context(): void
    {
        $this->createMcpTables();

        $serverModel = Server::query()->create([
            'name' => 'Taxilanz Hoteles Laravel MCP',
            'slug' => 'taxilanz-hoteles-laravel',
            'version' => '1.0.0',
            'transport' => 'web',
            'endpoint' => '/mcp/taxilanz-hoteles-laravel',
            'is_active' => true,
        ]);

        Tool::query()->create([
            'server_id' => $serverModel->id,
            'name' => 'taxilanz-hoteles-mcp-info',
            'title' => 'Taxilanz Hoteles MCP Info',
            'description' => 'Returns MCP server information.',
            'handler_code' => 'return ["ok" => true];',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->app->instance('request', Request::create('/mcp/taxilanz-hoteles-laravel', 'POST'));
        DynamicServer::registerEndpoint('mcp/taxilanz-hoteles-laravel', $serverModel->id);

        $server = new DynamicServer(new FakeTransporter);

        $this->assertSame(
            ['taxilanz-hoteles-mcp-info'],
            $server->createContext()->tools()->map(fn ($tool) => $tool->name())->all(),
        );
    }

    public function test_promptlyagent_presets_install_dynamic_servers_and_tools(): void
    {
        $this->createMcpTables();

        $tools = app(PromptlyAgentMcpPresetCatalog::class)->installServer('promptly-knowledge');

        $server = Server::query()->where('slug', 'promptly-knowledge')->first();

        $this->assertNotNull($server);
        $this->assertSame('/mcp/promptly-knowledge', $server->endpoint);
        $this->assertSame(['auth:sanctum', 'throttle:100,1'], $server->middleware);
        $this->assertCount(3, $tools);
        $this->assertDatabaseHas('tools', [
            'server_id' => $server->id,
            'name' => 'search_knowledge',
        ]);

        $searchTool = Tool::query()->where('name', 'search_knowledge')->firstOrFail();

        $this->assertSame('query', $searchTool->input_schema[0]['name']);
        $this->assertTrue($searchTool->annotations['isReadOnly']);
    }

    private function createMcpTables(): void
    {
        Schema::dropIfExists('tools');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('prompts');
        Schema::dropIfExists('servers');

        Schema::create('servers', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('version')->default('1.0.0');
            $table->text('instructions')->nullable();
            $table->string('transport')->default('web');
            $table->string('endpoint')->nullable();
            $table->json('middleware')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tools', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('title');
            $table->text('description');
            $table->json('input_schema')->nullable();
            $table->json('output_schema')->nullable();
            $table->text('handler_code');
            $table->json('annotations')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('resources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('title');
            $table->text('description');
            $table->string('uri');
            $table->string('uri_template')->nullable();
            $table->string('mime_type')->default('text/plain');
            $table->text('content')->nullable();
            $table->text('handler_code')->nullable();
            $table->json('annotations')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('prompts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('title');
            $table->text('description');
            $table->json('arguments')->nullable();
            $table->json('messages')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }
}
