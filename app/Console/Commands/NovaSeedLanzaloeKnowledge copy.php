<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NovaAiKnowledge;
use App\Models\NovaAiProfile;
use App\Models\NovaBusiness;
use Illuminate\Console\Command;

final class NovaSeedLanzaloeKnowledge extends Command
{
    protected $signature = 'nova:seed-lanzaloe-knowledge {--business=lanzaloe}';

    protected $description = 'Seed Nova AI knowledge fragments for Lanzaloe products, categories, CMS pages and commercial assistant answers';

    public function handle(): int
    {
        $business = NovaBusiness::query()
            ->where('slug', $this->option('business'))
            ->orWhere('name', 'like', '%Lanzaloe%')
            ->first();

        if (! $business) {
            $this->error('No Nova business found for Lanzaloe.');

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
                        'source' => 'manual_sitemap_summary',
                        'source_url' => 'https://www.lanzaloe.com/es/sitemap',
                        'domain' => 'lanzaloe_commerce',
                    ],
                ],
            );
        }

        $this->info('Lanzaloe AI knowledge fragments seeded for Nova.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{title:string, content:string}>
     */
    private function fragments(): array
    {
        return [
            [
                'title' => 'Lanzaloe - Categorías principales',
                'content' => 'Lanzaloe ofrece productos organizados por categorías: Aloe, Animales, Argán, Baño e Higiene, Cochinilla, Cremas Día, Cremas Noche, Cuidado corporal, Cuidado facial, Ecológica, Gourmet y Hogar, Kits, Salud y Pureza y Vinoterapia. Si el cliente pide información general, orientar hacia la categoría más adecuada y ofrecer ayuda para elegir producto o comprar.',
            ],
            [
                'title' => 'Lanzaloe - Productos Aloe Vera',
                'content' => 'Productos destacados con Aloe Vera: Gel Puro 100% ECOLÓGICO, Gel Puro 100% Aloe Vera en formatos 100ml, 250ml, 500ml y 1000ml, Gel Hidratante Aloe Vera After Sun, Gel de Baño Aloe Vera, Champú Aloe Vera, Gel dental Aloe Vera, Body Milk Aloe Vera, Crema de Manos, Crema de Pies de Aloe Vera, Protector Labial, Sales de Baño Aloe Vera y Jugo Puro Aloe Vera como complemento alimenticio en formatos 250ml, 500ml y 1000ml.',
            ],
            [
                'title' => 'Lanzaloe - Cosmética ecológica y cuidado facial',
                'content' => 'Lanzaloe dispone de cosmética ecológica y facial: Crema Hidratante Bio-Activa de día, Contorno de ojos Bio-Anti-Aging 100% ecológico, Crema Bio-Hidratante 100ml y 200ml, Crema Facial Bio-Anti-Aging de noche 100% ecológica, Kit Cremas Ecológicas con Aloe Vera y Kit Eco de cremas faciales. Recomendar según necesidad: hidratación, anti-aging, día, noche, contorno de ojos o rutina facial completa.',
            ],
            [
                'title' => 'Lanzaloe - Vinoterapia y Malvasía Volcánica',
                'content' => 'La línea de vinoterapia y Malvasía Volcánica incluye Aceite Reafirmante de Malvasía Volcánica, Body Milk de Malvasía Volcánica, Caja Malvasía Volcánica, Champú de Malvasía Volcánica, Crema Facial de Día de Malvasía Volcánica, Crema Facial de Noche de Malvasía Volcánica, Crema Hidra Anti-Aging Malvasía Volcánica 100ml y 200ml, Desodorante de Malvasía Volcánica, Gel de Baño de Malvasía Volcánica, Jabón Artesanal de Malvasía Volcánica y sales de baño de Malvasía Volcánica.',
            ],
            [
                'title' => 'Lanzaloe - Argán, cochinilla y cuidado corporal',
                'content' => 'Lanzaloe también ofrece Aceite Puro de Argán, Crema de Argán 100ml y 200ml, Jabón con Aceite de Argán, Sales de baño con Aceite de Argán, Body Milk Cochinilla, Gel de baño Cochinilla, Jabón artesanal Cochinilla, Jabón de Aloe Vera y Cochinilla, Crema hidratante Cochinilla 100ml y pintalabios de Cochinilla en tonos Cherry y Mora.',
            ],
            [
                'title' => 'Lanzaloe - Kits, viajes y regalos',
                'content' => 'Para regalo o viaje, Lanzaloe ofrece Kit de Viaje, Travel Set Aqua, Travel Set Aqua Tuno, Travel Set Bath Spa Malvasía, Travel Set Bio, Travel Set Body, Travel Set Body Malvasía, Travel Set Hydra Spa Malvasía y Travel Set Spa. Si el cliente no sabe qué elegir, sugerir kits o travel sets como opción práctica para probar varios productos o regalar.',
            ],
            [
                'title' => 'Lanzaloe - Gourmet y hogar',
                'content' => 'En Gourmet y Hogar destacan Mermelada de Tuno con Aloe 99gr, Mermelada de Tuno y Aloe Vera 250gr, Mermeladas Sabores de Canarias, Porta Incienso, Jaboncillos de Glicerina Aloe Vera y productos relacionados con bienestar y hogar. Son opciones interesantes para regalos vinculados a Lanzarote y productos locales.',
            ],
            [
                'title' => 'Lanzaloe - Animales',
                'content' => 'Para animales, Lanzaloe ofrece Champú para perros con Aloe Vera y Champú para perros con Aloe Vera 1L. También dispone de contenido informativo sobre Aloe Vera para perros como potenciador del sistema inmunológico y Aloe Vera para la piel del gato. Ante dudas veterinarias, recomendar consultar a un profesional y usar la información como orientación general.',
            ],
            [
                'title' => 'Lanzaloe - Salud, pureza y protección solar',
                'content' => 'La categoría Salud y Pureza incluye Jugo Puro Aloe Vera como complemento alimenticio, Gel Puro Aloe Vera, Gel Relax, Gel Hidroalcohólico 70% en 100ml y 250ml, Desodorante 100% ecológico con Aloe Vera, Protector Solar SPF 30 en 100ml y 250ml y Gel Hidratante Aloe Vera After Sun. Orientar según necesidad: hidratación, after sun, higiene, protección solar o complemento.',
            ],
            [
                'title' => 'Lanzaloe - Información corporativa y visitas',
                'content' => 'Páginas informativas relevantes: Acerca de LANZALOE, Bienvenido a Lanzaloe, Compromiso de calidad, El equipo, El origen del Aloe Vera, Información de la empresa, La producción, Misión y Filosofía, Propiedades del Aloe Vera, Beneficios y Propiedades, Visitar Lanzaloe Park, ¿Dónde estamos? y ¿Qué es el Aloe Vera? Si el cliente pregunta por visitar Lanzaloe, ofrecer información de Lanzaloe Park y proponer ayudar con la visita o compra de productos.',
            ],
            [
                'title' => 'Lanzaloe - Contenidos frecuentes y asesoramiento',
                'content' => 'Lanzaloe cuenta con contenidos sobre Aloe Vera para acné, cabello, embarazo y posparto, picaduras, piel madura, eczema y psoriasis, caspa, deporte, trasplante de Aloe Vera, depuración del organismo, cuidado solar, especies de Aloe, pieles, productos veganos, argán, karité, cochinilla, semilla de uva, vinoterapia y superalimentos. El bot debe usar estos temas para orientar y ofrecer productos relacionados, sin prometer efectos médicos.',
            ],
            [
                'title' => 'Lanzaloe - Respuesta comercial sugerida',
                'content' => 'Si el cliente pide información general de Lanzaloe, responder: En Lanzaloe trabajamos productos naturales de Lanzarote con Aloe Vera, cosmética ecológica, vinoterapia con Malvasía Volcánica, cuidado facial y corporal, kits de viaje, productos gourmet y opciones para animales. ¿Quieres que te recomiende algo para cuidado facial, cuerpo, after sun, vinoterapia, regalo o visita a Lanzaloe Park? Cerrar siempre ofreciendo ayuda para elegir producto, comprar o visitar.',
            ],
        ];
    }
}
