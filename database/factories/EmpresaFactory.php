<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
class EmpresaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codeempresa' => $this->faker->unique()->numberBetween(1000, 999999),
            'empresa' => $this->faker->company(),
            'nif' => $this->faker->optional()->regexify('[A-Z]{1}[0-9]{8}'),
            'direccion' => $this->faker->optional()->streetAddress(),
            'codigopostal' => $this->faker->optional()->postcode(),
            'telefono' => $this->faker->optional()->phoneNumber(),
            'fax' => $this->faker->optional()->phoneNumber(),
            'web' => $this->faker->optional()->url(),
            'email' => $this->faker->optional()->companyEmail(),
            'cuentacorriente' => $this->faker->optional()->iban(),
            'tarjetacredito' => $this->faker->optional()->creditCardNumber(),
            'tipocredito' => $this->faker->optional()->word(),
            'fechaalta' => $this->faker->optional()->date(),
            'fechamodificado' => $this->faker->optional()->date(),
            'fechafacturado' => $this->faker->optional()->date(),
            'fechabaja' => $this->faker->optional()->date(),
            'usuario' => $this->faker->optional()->numberBetween(1, 100),
            'observaciones' => $this->faker->optional()->sentence(),
            'logoempresa' => $this->faker->optional()->word(),
            'logopublicidad' => $this->faker->optional()->word(),
            'administrador' => $this->faker->optional()->name(),
            'poblacion' => $this->faker->optional()->city(),
            'porcentajeexplotacion' => $this->faker->optional()->randomFloat(2, 0, 100),
        ];
    }
}
