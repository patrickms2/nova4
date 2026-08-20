<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GastoTransactionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_gasto_generates_codigo_and_defaults(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $gasto = new Gasto([
            'descripcion' => 'Gasto de prueba',
            'total' => 107.00,
            'fecha' => '2026-07-13',
        ]);
        $gasto->user_id = $user->id;
        $gasto->save();

        $this->assertSame('G-20260713-0001', $gasto->codigo);
        $this->assertSame('expense', $gasto->type);
        $this->assertSame(107.0, (float) $gasto->base_imponible);
    }

    public function test_gasto_alias_attributes_map_to_spanish_columns(): void
    {
        $gasto = new Gasto([
            'description' => 'Alias de prueba',
            'amount' => 53.50,
            'date' => '2026-07-13',
        ]);

        $this->assertSame('Alias de prueba', $gasto->descripcion);
        $this->assertSame('Alias de prueba', $gasto->description);
        $this->assertSame(53.5, (float) $gasto->total);
        $this->assertSame(53.5, (float) $gasto->amount);
        $this->assertSame('2026-07-13', $gasto->fecha->toDateString());
        $this->assertSame('2026-07-13', $gasto->date->toDateString());
    }

    public function test_total_is_calculated_from_base_and_impuesto(): void
    {
        $gasto = new Gasto([
            'descripcion' => 'Con base e impuesto',
            'base_imponible' => 100.00,
            'impuesto' => 7.00,
            'fecha' => '2026-07-13',
        ]);
        $gasto->save();

        $this->assertSame(107.0, (float) $gasto->total);
    }
}
