<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codcliente' => $this->faker->unique()->regexify('CLI[0-9]{6}'),
            'codcontabilidad' => $this->faker->optional()->regexify('[0-9]{6}'),
            'nombretotal' => $this->faker->name(),
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'identificacion' => $this->faker->optional()->regexify('[0-9]{8}[A-Z]{1}'),
            'dni' => $this->faker->optional()->regexify('[0-9]{8}[A-Z]{1}'),
            'tipo' => $this->faker->optional()->word(),
            'sexo' => $this->faker->optional()->randomElement(['M', 'F']),
            'domicilio' => $this->faker->optional()->streetAddress(),
            'poblacion' => $this->faker->optional()->city(),
            'codigopostal' => $this->faker->optional()->postcode(),
            'provincia' => $this->faker->optional()->state(),
            'pais' => $this->faker->optional()->country(),
            'nacionalidad' => $this->faker->optional()->country(),
            'telefono' => $this->faker->optional()->phoneNumber(),
            'fax' => $this->faker->optional()->phoneNumber(),
            'movil' => $this->faker->optional()->phoneNumber(),
            'trabajo' => $this->faker->optional()->phoneNumber(),
            'web' => $this->faker->optional()->url(),
            'email' => $this->faker->optional()->email(),
            'cuentacorriente' => $this->faker->optional()->iban(),
            'tarjetacredito' => $this->faker->optional()->creditCardNumber(),
            'tipocredito' => $this->faker->optional()->word(),
            'fechaalta' => $this->faker->optional()->date(),
            'fechamodificado' => $this->faker->optional()->date(),
            'fechafacturado' => $this->faker->optional()->date(),
            'fechabaja' => $this->faker->optional()->date(),
            'usuario' => $this->faker->optional()->numberBetween(1, 100),
            'observaciones' => $this->faker->optional()->sentence(),
            'domiciliado' => $this->faker->boolean(),
            'empresa_id' => null,
        ];
    }

    public function forEmpresa(?Empresa $empresa = null): static
    {
        return $this->state(fn () => [
            'empresa_id' => $empresa?->id ?? Empresa::factory(),
        ]);
    }
}
