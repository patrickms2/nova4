<?php

namespace Tests\Feature;

use App\Ai\Agents\GastoOcrAgent;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GastoOcrApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_gasto_from_receipt_image(): void
    {
        Storage::fake('public');

        GastoOcrAgent::fake([
            [
                'empresa' => 'GASOLINERA CEPSA',
                'fecha' => '2026-07-10',
                'base_imponible' => 46.73,
                'impuesto' => 3.27,
                'total' => 50.00,
                'concepto' => 'Combustible',
            ],
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/gastos/ocr', [
            'image' => UploadedFile::fake()->image('ticket.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 50)
            ->assertJsonPath('data.proveedor', 'GASOLINERA CEPSA');

        $this->assertDatabaseHas('gastos', [
            'descripcion' => 'GASOLINERA CEPSA - Combustible',
            'total' => 50.00,
        ]);

        $this->assertDatabaseHas('clientes', [
            'nombretotal' => 'GASOLINERA CEPSA',
        ]);

        Storage::disk('public')->assertExists($response->json('data.documento'));
    }

    public function test_reuses_existing_proveedor(): void
    {
        Storage::fake('public');

        $proveedor = Cliente::create([
            'codcliente' => 'PROV-1',
            'nombretotal' => 'GASOLINERA CEPSA',
            'fechaalta' => now()->toDateString(),
        ]);

        GastoOcrAgent::fake([
            [
                'empresa' => 'Gasolinera Cepsa',
                'fecha' => '2026-07-10',
                'base_imponible' => null,
                'impuesto' => null,
                'total' => 20.00,
                'concepto' => 'Combustible',
            ],
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/gastos/ocr', [
            'image' => UploadedFile::fake()->image('ticket.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.proveedor_id', $proveedor->id);

        $this->assertSame(1, Cliente::query()->where('nombretotal', 'like', '%CEPSA%')->count());
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/gastos/ocr', [
            'image' => UploadedFile::fake()->image('ticket.jpg'),
        ])->assertUnauthorized();
    }
}
