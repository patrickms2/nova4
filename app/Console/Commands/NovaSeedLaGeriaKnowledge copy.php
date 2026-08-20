<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NovaAiKnowledge;
use App\Models\NovaAiProfile;
use App\Models\NovaBusiness;
use Illuminate\Console\Command;

final class NovaSeedLaGeriaKnowledge extends Command
{
    protected $signature = 'nova:seed-la-geria-knowledge {--business=la-geria}';

    protected $description = 'Seed Nova AI knowledge fragments for La Geria using the Sirvo assistant pattern';

    public function handle(): int
    {
        $business = NovaBusiness::query()
            ->where('slug', $this->option('business'))
            ->orWhere('name', 'like', '%Geria%')
            ->first();

        if (! $business) {
            $this->error('No Nova business found for La Geria.');

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
                        'source' => 'sirvo_ai_config_pattern',
                        'domain' => 'la_geria_visits',
                    ],
                ],
            );
        }

        $this->info('La Geria AI knowledge fragments seeded for Nova.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{title:string, content:string}>
     */
    private function fragments(): array
    {
        return [
            [
                'title' => 'Visitas',
                'content' => 'Visitas guiadas y wine tours en La Geria: descubre el proceso del vino entre paisajes volcánicos únicos. La visita guiada consiste en un paseo por la finca de viñedos y la bodega acompañado por el guía, con explicación de las técnicas de cultivo, la tradición vitivinícola de La Geria y el secreto del vino Malvasía. La experiencia termina con una cata de tres vinos. Duración aproximada: 50 minutos. Menores de 15 años gratis. Impuestos incluidos (IGIC 7%). Precio: 15€.',
            ],
            [
                'title' => 'Avisos para visita guiada',
                'content' => 'Avisos para visita guiada: el tour no es accesible para personas con dificultad para caminar. Los carritos de bebé no podrán utilizarse durante la visita, aunque hay espacio para guardarlos. Para grupos superiores a 8 personas, deben comunicarse con la bodega vía correo electrónico. Si el cliente quiere realizar la visita guiada en otro idioma, debe consultar días y horarios disponibles en otros idiomas. Para dudas, llamar al 928 17 31 78 o escribir a bodegalageria@lageria.com.',
            ],
            [
                'title' => 'Servicios',
                'content' => 'En La Geria se pueden disfrutar catas de vino y visitas guiadas inmersas en la tradición vitivinícola de Lanzarote. Los recorridos permiten conocer los viñedos plantados en terreno volcánico y el proceso de producción del vino. En la visita se puede disfrutar del paisaje excepcional, visitar la bodega, catar sus vinos y comer algo en su bar-cafetería.',
            ],
            [
                'title' => 'Vinos de La Geria',
                'content' => 'Principales vinos de Bodega La Geria disponibles en tienda online: La Geria Tinto Joven 75cl 15€, La Geria Malvasía Volcánica Blanco Seco 75cl 16€, La Geria Malvasía Volcánica Blanco Semidulce 75cl 17€, La Geria Rosado 75cl 15€, Manto Malvasía Volcánica Seco 75cl 19€, La Geria Moscatel Dulce 50cl 20€, La Geria Malvasía Volcánica Blanco Dulce 50cl 21€, Manto Malvasía Volcánica Semidulce 75cl 20€, Manto Tinto Selección 75cl 23€, La Geria Blanco Seco Ecológico 75cl 16€, La Geria Tinto Ecológico 75cl 17€, MANTO Rosado 19€, MANTO DIEGO 3 meses barrica 20€. Los precios y disponibilidad pueden variar.',
            ],
            [
                'title' => 'Historia de Bodega La Geria',
                'content' => 'Bodega La Geria fue construida a finales del siglo XIX por la familia Rijo. En 1993 fue adquirida por sus actuales propietarios, la familia Melián. Desde entonces, la bodega combina las tradiciones antiguas de viticultura con tecnología avanzada de elaboración y control de calidad.',
            ],
            [
                'title' => 'Ubicación y paisaje de La Geria',
                'content' => 'Bodega La Geria está entre las bodegas más visitadas de España por estar ubicada en La Geria, zona vitivinícola emblemática de Lanzarote. Desde la bodega se aprecian viñedos plantados en hoyos excavados en la arena y rodeados de ceniza volcánica, con vistas hacia los volcanes del Parque Nacional de Timanfaya.',
            ],
            [
                'title' => 'Vinos tintos de La Geria',
                'content' => 'Vinos tintos disponibles: La Geria Tinto Joven 75cl 15€, color picota intenso con ribetes violáceos, aromas de mora, cereza madura, torrefactos, vainilla y suave madera; en boca es equilibrado, redondo y sedoso. Manto Tinto Selección 75cl 23€, elaborado con Syrah, Merlot, Tintilla y Listán Negro de cepas propias. La Geria Tinto Ecológico 75cl 17€, elaborado a partir de uvas recogidas en la propia finca. MANTO TINTO 3 meses barrica 22€ puede estar sin stock.',
            ],
            [
                'title' => 'Vinos Malvasía y blancos de La Geria',
                'content' => 'Vinos blancos y Malvasía: La Geria Malvasía Volcánica Blanco Seco 75cl 16€, elaborado con vendimia manual y selección rigurosa de uva en laderas y valles de volcanes. La Geria Malvasía Volcánica Blanco Semidulce 75cl 17€. Manto Malvasía Volcánica Seco 75cl 19€, elaborado con la mejor uva seleccionada manualmente y fermentación con levaduras autóctonas de Lanzarote. La Geria Blanco Seco Ecológico 75cl 16€, elaborado con uvas de la propia finca. Manto Malvasía Volcánica Semidulce 75cl 20€.',
            ],
            [
                'title' => 'Vinos dulces, moscatel y ediciones limitadas',
                'content' => 'Vinos dulces: La Geria Moscatel Dulce 50cl 20€, elaborado con uva moscatel vendimiada manualmente en madurez avanzada. La Geria Malvasía Volcánica Blanco Dulce 50cl 21€. Antigua Moscatel Dulce edición limitada 50cl 59€, vino de solera desde 1996 conservado durante 20 años en barricas de roble; puede estar sin stock. Antigua Malvasía Volcánica Dulce edición limitada 50cl 49€, elaborada mediante pasificación de la uva Malvasía Volcánica en cenizas del volcán; puede estar sin stock. MANTO DIEGO 3 meses barrica 20€.',
            ],
            [
                'title' => 'Vinos rosados de La Geria',
                'content' => 'Vinos rosados: La Geria Rosado 75cl 15€, elaborado tras selección rigurosa de la uva, despalillado y contacto del mosto con la piel durante 12 horas. MANTO Rosado 19€. Son opciones recomendables para clientes que buscan vinos frescos o una alternativa al blanco y tinto.',
            ],
            [
                'title' => 'Eventos y reservas LatePoint',
                'content' => 'Productos de reserva y eventos: Visita Guiada es un servicio virtual LatePoint con precio 15€, oculto en catálogo público y destinado a reservas. Cata es un evento virtual relacionado con la visita y cata de varios vinos. Si el cliente quiere reservar, pedir día, hora, número de personas y nombre. Para grupos superiores a 8 personas, recomendar contactar por email.',
            ],
            [
                'title' => 'Taberna La Cepa - Información general',
                'content' => 'Taberna La Cepa es el restaurante de Bodega La Geria, con vistas al Paraje Natural de La Geria. Ofrece pinchos acompañados de vinos Malvasía La Geria y una carta con platos típicos de Lanzarote como carne de cabra, garbanzas y carne de cochino en adobo. Está abierta todos los días de 9:00 a 18:00. Cocina hasta las 16:30. Para información y reservas llamar al 828 180 501. Aforo limitado.',
            ],
            [
                'title' => 'Taberna La Cepa - Para compartir',
                'content' => 'Platos para compartir en Taberna La Cepa: Huevos rotos con jamón y papas fritas 12,50€, huevos rotos con chorizo y papas fritas 10,90€, albóndigas en salsa de tomate 10,90€, vueltas de ternera con papas fritas 12,90€, entrecot de lomo alto Angus fileteado para compartir con papas 16,50€, carne de cabra estofada 13,90€, estofado de ternera con hortalizas y papas 10,50€, caldo millo 13,90€ y garbanzas 8,90€.',
            ],
            [
                'title' => 'Taberna La Cepa - Entrantes y ensaladas',
                'content' => 'Entrantes y ensaladas de Taberna La Cepa: Pan con ajo 4,50€, Ensalada Taberna 11,90€ con lechugas variadas, cebolla roja, tomate, papaya, melón, salmón ahumado, gambas, aceitunas, espárragos, aguacate y vinagreta de miel. Ensalada de pollo crujiente 11,90€ con lechuga, manzana, aguacate, huevo cocido y queso rallado.',
            ],
            [
                'title' => 'Taberna La Cepa - Tapas y platos locales',
                'content' => 'Tapas y platos locales de Taberna La Cepa: Timbal de aguacate, tomate y atún 9,90€, surtido de montaditos a elección 12,00€, papas arrugadas 3,90€, pimientos de Padrón 5,90€, cazuela de langostinos al ajillo 9,90€, cazuela de pulpo, langostinos y champiñones al ajillo 13,90€, croquetas de pollo, atún o espinacas 9,50€, queso frito de Lanzarote con dulce de higos 10,50€, fritos de pescado con ali-oli 9,90€, chorizo a la sidra 6,50€, tabla Taberna 14,90€, surtido de quesos de Lanzarote 12,50€ y caracoles 9,90€.',
            ],
            [
                'title' => 'Taberna La Cepa - Platos por encargo',
                'content' => 'En Taberna La Cepa hay platos por encargo como sancocho de cherne, ropa vieja, costillas con piña y paella. Si el cliente pregunta por estos platos, indicar que son por encargo y recomendar llamar al 828 180 501 para confirmar disponibilidad y reservar.',
            ],
            [
                'title' => 'Visita guiada multidioma',
                'content' => 'ES: Nuestra visita guiada consiste en un paseo por nuestra finca de viñedos y a la bodega acompañado por el guía, terminando con una cata de tres vinos. Duración: 50 minutos. Menores de 15 años gratis. Precio: 15€. EN: Our guided tour takes 50 min. The tour consists of a walk through our vineyard and winery, ending with a tasting of three wines. Under 15 years old is free. Price: 15€. FR: Notre visite guidée dure environ 1 heure. Elle consiste en une promenade à travers notre vignoble et notre cave, et se termine par une dégustation de trois vins. Prix: 15€. DE: Unsere Führung beinhaltet einen Spaziergang durch unser Weingut und unsere Kellerei in Begleitung eines Guides und schließt mit einer Weinprobe ab. Die Tour dauert 50 Minuten. Preis: 15€.',
            ],
        ];
    }
}
