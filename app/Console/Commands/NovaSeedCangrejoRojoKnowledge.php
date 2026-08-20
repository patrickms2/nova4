<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NovaAiKnowledge;
use App\Models\NovaAiProfile;
use App\Models\NovaBusiness;
use Illuminate\Console\Command;

final class NovaSeedCangrejoRojoKnowledge extends Command
{
    protected $signature = 'nova:seed-cangrejo-rojo-knowledge {--business=cangrejo-rojo}';

    protected $description = 'Seed Nova AI knowledge fragments for Restaurante El Cangrejo Rojo';

    public function handle(): int
    {
        $business = NovaBusiness::query()
            ->where('slug', $this->option('business'))
            ->orWhere('name', 'like', '%Cangrejo Rojo%')
            ->first();

        if (! $business) {
            $this->error('No Nova business found for Cangrejo Rojo. Create it first, for example slug cangrejo-rojo.');

            return self::FAILURE;
        }

        $profile = NovaAiProfile::query()
            ->where('nova_business_id', $business->id)
            ->where('status', 'active')
            ->first();

        foreach ($this->fragments() as $fragment) {
            NovaAiKnowledge::query()->updateOrCreate(
                [
                    'nova_business_id' => $business->id,
                    'title' => $fragment['title'],
                ],
                [
                    'nova_ai_profile_id' => $profile?->id,
                    'content' => $fragment['content'],
                    'status' => 'active',
                    'metadata' => [
                        'source' => 'manual_restaurant_knowledge',
                        'domain' => 'cangrejo_rojo_restaurant',
                    ],
                ],
            );
        }

        $this->info('Cangrejo Rojo AI knowledge fragments seeded for Nova.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{title:string, content:string}>
     */
    private function fragments(): array
    {
        return [
            [
                'title' => 'El Cangrejo Rojo - Descripción',
                'content' => 'El Cangrejo Rojo es uno de los mejores restaurantes en primera línea de mar en Puerto del Carmen, Lanzarote. Está especializado en pescados y mariscos frescos, y también ofrece carnes, arroces y cocina tradicional canaria. Tiene una terraza frente al océano Atlántico y es ideal para parejas, familias y grupos que buscan una experiencia culinaria auténtica. Dirección: C. Roque Nublo, 11, 35510 Puerto del Carmen, Las Palmas. Teléfono: 928 51 21 91. Email: info@restaurantecangrejorojo.com. Reservas: https://www.restaurantecangrejorojo.com/reservar/. Carta: https://www.restaurantecangrejorojo.com/la-carta/.',
            ],
            [
                'title' => 'El Cangrejo Rojo - Historia y Puerto del Carmen',
                'content' => 'El Cangrejo Rojo comenzó su aventura hace 40 años, impulsado por la pasión por la hostelería y por compartir los sabores de Lanzarote. Su carta refleja los sabores de la isla, con pescados y mariscos frescos, carnes de calidad y vinos lanzaroteños. Está en pleno corazón de Puerto del Carmen, uno de los lugares más típicos de Lanzarote, con vistas al océano Atlántico y un ambiente especial. Puerto del Carmen destaca por su clima cálido, playas doradas y ambiente tradicional canario.',
            ],
            [
                'title' => 'El Cangrejo Rojo - La Carta',
                'content' => 'Nunca dar la carta completa. Dar una descripción de la carta, mencionar platos destacados y redirigir siempre a la carta completa: https://www.restaurantecangrejorojo.com/la-carta/. La carta incluye entrantes, ensaladas, sopas, arroces, pastas, pizzas, platos vegetarianos, carnes, pescados, mariscos y postres. Platos destacados: pulpo a la brasa con mojo verde, dados de atún teriyaki, gambas al ajillo, croquetas caseras, arroz caldoso con bogavante, paellas, solomillo al vino tinto con foie, parrillada de carne, cherne, parrillada de pescados y mariscos, langostinos XL, salmón, calamares y postres como tiramisú, bienmesabe, brownie y tarta de queso.',
            ],
            [
                'title' => 'El Cangrejo Rojo - Entrantes y ensaladas destacados',
                'content' => 'Entrantes destacados: alitas de pollo en salsa barbacoa 12,25€, pimientos de Padrón 9,50€, dados de atún Teriyaki 16,75€, pan a la catalana con paletilla ibérica 12,50€, pulpo a la brasa con mojo verde 16,90€, quesos canarios con dulce de membrillo 13,86€, carpaccio de ternera 16,90€, jamón ibérico 19,50€, queso asado con confitura de tomate y mojo verde 13,50€, gambas al ajillo 11,95€, mejillones 15,90€, salmón ahumado de Lanzarote 16,95€ y croquetas caseras 8,90€. Ensaladas destacadas: ensalada de tomate, albahaca y queso blanco 9,50€, cocktail de gambas 11,95€ y tartar de aguacate y manzana con langostinos y vieiras 14,50€.',
            ],
            [
                'title' => 'El Cangrejo Rojo - Arroces, carnes y pescados destacados',
                'content' => 'Arroces y pastas destacadas: arroz caldoso con bogavante 45€ por persona, arroz negro con langostinos y calamares 16,50€ por persona mínimo 2 personas, paella mixta 16,50€ por persona mínimo 2 personas y paella de pescados y mariscos 17,50€ por persona mínimo 2 personas. Carnes destacadas: solomillo al vino tinto con foie 25,30€, parrillada de carne 22,50€, solomillo a la estaca de carabinero 28,90€, hamburguesa especial Cangrejo Rojo 15,90€, paletilla de cordero 26,90€ y presa ibérica a la piedra 24,95€. Pescados y mariscos destacados: cherne 21,90€, parrillada de pescados y mariscos 21,90€, langostinos XL 20,90€, lubina o dorada 20,90€, salmón 20,90€, atún en tomate con albahaca 18,50€ y calamares 17,50€.',
            ],
            [
                'title' => 'El Cangrejo Rojo - Reservas y cancelaciones',
                'content' => 'Para reservar en El Cangrejo Rojo, pedir día, hora, número de personas y nombre. Web de reservas: https://www.restaurantecangrejorojo.com/reservar/. Si un cliente quiere cancelar una reserva y no se encuentra por teléfono, indicar que puede cancelarla a través del enlace que le llegó al correo. Si no le llegó ningún correo, debe llamar al restaurante al 928 51 21 91.',
            ],
            [
                'title' => 'El Cangrejo Rojo - Redes y contacto',
                'content' => 'Contacto de El Cangrejo Rojo: Teléfono 928 51 21 91. Email info@restaurantecangrejorojo.com. Dirección C. Roque Nublo, 11, 35510 Puerto del Carmen, Las Palmas. Instagram: https://www.instagram.com/elcangrejo_rojo/. Facebook: https://www.facebook.com/p/Restaurante-Cangrejo-Rojo-100069892635098/. Carta: https://www.restaurantecangrejorojo.com/la-carta/. Reservas: https://www.restaurantecangrejorojo.com/reservar/.',
            ],
        ];
    }
}
