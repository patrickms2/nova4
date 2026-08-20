<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NovaAiKnowledge;
use App\Models\NovaAiProfile;
use App\Models\NovaBusiness;
use Illuminate\Console\Command;

final class NovaSeedTaxiKnowledge extends Command
{
    protected $signature = 'nova:seed-taxi-knowledge {--business=taxilanz}';

    protected $description = 'Seed Nova AI knowledge fragments for taxi, transfers and taxi excursions';

    public function handle(): int
    {
        $business = NovaBusiness::query()
            ->where('slug', $this->option('business'))
            ->orWhere('name', 'like', '%Taxi%')
            ->orWhere('name', 'like', '%Taxilanz%')
            ->orWhere('business_type', 'taxi')
            ->first();

        if (! $business) {
            $this->error('No Nova taxi business found. Create a taxi business first, for example slug taxilanz.');

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
                        'source' => 'manual_taxi_seed',
                        'domain' => 'taxi_transfers_excursions',
                    ],
                ],
            );
        }

        $this->info('Taxi AI knowledge fragments seeded for Nova.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{title:string, content:string}>
     */
    private function fragments(): array
    {
        return [
            [
                'title' => 'Taxi - Servicios principales',
                'content' => 'El asistente puede ayudar con taxis, traslados, recogidas, aeropuerto, puerto, hoteles, restaurantes, bodegas, excursiones en taxi y rutas por la isla. Para preparar el servicio necesita origen, destino o ruta, día, hora, número de personas y si hay maletas, niños, silla infantil o necesidades especiales.',
            ],
            [
                'title' => 'Taxi - Traslados',
                'content' => 'Para traslados, pedir origen, destino, fecha, hora, número de personas, teléfono de contacto y detalles importantes como número de vuelo, nombre del hotel, equipaje o si viajan niños. Si el traslado es al aeropuerto, confirmar hora recomendada de recogida según salida del vuelo.',
            ],
            [
                'title' => 'Taxi - Excursiones en taxi',
                'content' => 'Para excursiones en taxi, preguntar si el cliente quiere una ruta por volcanes, bodegas, playas, miradores, pueblos, norte de Lanzarote, sur de Lanzarote o una ruta personalizada. Pedir día, hora de salida, punto de recogida, duración aproximada y número de personas. Ofrecer preparar una propuesta de ruta.',
            ],
            [
                'title' => 'Taxi - CTA comercial',
                'content' => 'Cuando el cliente pida taxi, traslado o excursión, responder de forma directa: te ayudo a prepararlo. Indica origen, destino o ruta, día, hora y número de personas. Si pregunta por precio, explicar que se necesita ruta y horario para calcular o confirmar importe.',
            ],
            [
                'title' => 'Taxi - Contacto Taxilanz',
                'content' => 'Taxilanz ofrece servicio 24 horas en Lanzarote. Teléfono: +34 928 52 42 20. Email: info@taxilanz.com. Ubicación: Tías, Lanzarote. Recordar que domingos y festivos tienen incremento del 15% en la tarifa y que el 7% IGIC no está incluido.',
            ],
            [
                'title' => 'Taxi - Tarifas desde Playa Blanca',
                'content' => 'Tarifas taxi desde Playa Blanca: Aeropuerto 59€, Puerto del Carmen 45€, Matagorda 50€, Arrecife 59€, Los Mármoles puerto 64€, La Marina puerto 64€, Teguise 63€, Playa Honda 52€, Jardín del Cactus 72€, Costa Teguise 69€, La Santa 58€, Jameos del Agua 84€, Caleta de Famara 71€, Mirador del Río 91€, Órzola 101€, El Golfo 26€, La Geria 33€, Parque Nacional de Timanfaya 37€, Yaiza 24€, Playa Quemada 36€, Puerto Calero 40€, Haría 84€. Domingos y festivos +15%. IGIC 7% no incluido.',
            ],
            [
                'title' => 'Taxi - Tarifas desde Puerto del Carmen',
                'content' => 'Tarifas taxi desde Puerto del Carmen: Aeropuerto 25€, Arrecife 25€, Los Mármoles puerto 30€, La Marina puerto 27€, Playa Honda 26€, Costa Teguise 39€, Teguise 35€, Jardín del Cactus 44€, La Santa 48€, Jameos del Agua 64€, Caleta de Famara 45€, Mirador del Río 71€, Órzola ferry a La Graciosa 74€, El Golfo 37€, La Geria 21€, Parque Nacional de Timanfaya 37€, Yaiza 22€, Playa Quemada 21€, Puerto Calero 20€, Playa Blanca 52€. Domingos y festivos +15%. IGIC 7% no incluido.',
            ],
            [
                'title' => 'Taxi - Tarifas desde Matagorda',
                'content' => 'Tarifas taxi desde Matagorda: Aeropuerto 20€, Arrecife 22€, Los Mármoles puerto 30€, La Marina puerto 22€, Playa Honda 16€, Costa Teguise 34€, Teguise 34€, Jardín del Cactus 37€, La Santa 47€, Jameos del Agua 57€, Caleta de Famara 47€, Mirador del Río 71€, Órzola ferry a La Graciosa 68€, El Golfo 44€, La Geria 28€, Parque Nacional de Timanfaya 45€, Yaiza 30€, Playa Quemada 21€, Puerto Calero 20€, Playa Blanca 55€. Matagorda cubre zona de Puerto del Carmen desde Hotel Beatriz Costa hasta Hotel San Antonio. Domingos y festivos +15%. IGIC 7% no incluido.',
            ],
            [
                'title' => 'Taxi - Tarifas desde Costa Teguise',
                'content' => 'Tarifas taxi desde Costa Teguise: Aeropuerto 31€, Puerto del Carmen 36€, Arrecife 17€, Los Mármoles puerto 14€, La Marina puerto 17€, Teguise 24€, Playa Honda 24€, Jardín del Cactus 22€, Jameos del Agua 38€, Caleta de Famara 40€, La Santa 48€, Mirador del Río 52€, Órzola ferry a La Graciosa 51€, El Golfo 59€, La Geria 41€, Parque Nacional de Timanfaya 61€, Yaiza 47€, Playa Quemada 48€, Puerto Calero 44€, Playa Blanca 71€. Domingos y festivos +15%. IGIC 7% no incluido.',
            ],
            [
                'title' => 'Taxi - Tarifas desde Haría',
                'content' => 'Tarifas taxi desde Haría: Aeropuerto 53€, Puerto del Carmen 63€, Arrecife 46€, Teguise 25€, Órzola 18€, Jardín del Cactus 20€, La Santa 57€, Jameos del Agua 16€, Mirador del Río 14€, Playa Blanca 84€, El Golfo 82€, Parque Nacional de Timanfaya 67€, Puerto Calero 63€. Domingos y festivos +15%. IGIC 7% no incluido.',
            ],
            [
                'title' => 'Taxi - Tarifas desde Tinajo',
                'content' => 'Tarifas taxi desde Tinajo: Aeropuerto 35€, Puerto del Carmen 37€, Arrecife 29€, Tías 28€, Famara 35€, Teguise 27€, Costa Teguise 40€, Playa Honda 29€, Playa Blanca 47€, El Golfo 44€, Parque Nacional de Timanfaya 18€, Yaiza 25€, Puerto Calero 33€. Domingos y festivos +15%. IGIC 7% no incluido.',
            ],
            [
                'title' => 'Taxi - Tarifas desde La Santa Sport',
                'content' => 'Tarifas taxi desde La Santa Sport: Aeropuerto 47€, Jardín del Cactus 48€, Costa Teguise 51€, Jameos del Agua 59€, Mirador del Río 69€, Playa Blanca 56€, Playa Quemada 52€. Domingos y festivos +15%. IGIC 7% no incluido.',
            ],
            [
                'title' => 'Taxi - Rutas desde Playa Blanca',
                'content' => 'Rutas en taxi desde Playa Blanca. Ruta Sur desde Playa Blanca: visita al Parque Nacional de Timanfaya, Costa de las Lavas (Salinas del Janubio, Los Hervideros y El Golfo) y La Geria. Duración 4h. Precio 140€. Entrada Timanfaya no incluida: 12€ adulto y 6€ menor. Paseo a camello opcional 22€ por camello para 2 personas. Ruta Norte desde Playa Blanca: Jardín del Cactus, Mirador del Río, a elegir entre Jameos del Agua y Cueva de los Verdes, Monumento al Campesino, Teguise y La Geria panorámico. Duración 6h. Precio 200€. Entradas no incluidas: coste total por persona 21,50€ adulto y 10,75€ menor.',
            ],
            [
                'title' => 'Taxi - Rutas desde Puerto del Carmen',
                'content' => 'Rutas en taxi desde Puerto del Carmen. Ruta Sur desde Puerto del Carmen: visita al Parque Nacional de Timanfaya, Costa de las Lavas (Salinas del Janubio, Los Hervideros y El Golfo), vista de La Geria en panorámico y parada en Monumento del Campesino. Duración 4h. Precio 140€. Entrada Timanfaya no incluida: 12€ adulto y 6€ menor. Paseo a camello opcional 22€ por camello para 2 personas. Ruta Norte desde Puerto del Carmen: Jardín del Cactus, Mirador del Río, a elegir entre Jameos del Agua y Cueva de los Verdes, Monumento al Campesino, Teguise y La Geria panorámico. Duración 6h. Precio 200€. Entradas no incluidas: coste total por persona 21,50€ adulto y 10,75€ menor.',
            ],
            [
                'title' => 'Taxi - Rutas desde Costa Teguise',
                'content' => 'Rutas en taxi desde Costa Teguise. Ruta Sur desde Costa Teguise: visita al Parque Nacional de Timanfaya, Costa de las Lavas (Salinas del Janubio, Los Hervideros y El Golfo) y vista de La Geria en panorámico. Duración 5h. Precio 170€. Entrada Timanfaya no incluida: 12€ adulto y 6€ menor. Paseo a camello opcional 22€ por camello para 2 personas. Ruta Norte desde Costa Teguise: Jardín del Cactus, Mirador del Río, a elegir entre Jameos del Agua y Cueva de los Verdes, y visita a Teguise. Duración 4h. Precio 140€. Entradas no incluidas: coste total por persona 21,50€ adulto y 10,75€ menor.',
            ],
            [
                'title' => 'Taxi - Senderismo y traslados ida y vuelta',
                'content' => 'Taxilanz ofrece traslados de ida y vuelta para rutas de senderismo al corazón de Lanzarote. Hay 10 senderos disponibles con ficha técnica, detalles y precio según localidad de traslado. Senderos: 1 Caldera de los Cuervos y Montaña Colorada, 2 Caldera Blanca, 3 Haría, El Bosquecillo y Famara, 4 Camino de los Gracioseros y bajada del risco, 5 Volcán de la Corona, 6 La Geria, 7 Vuelta a Pico Redondo, 8 Subida a Montaña Blanca, 9 visita la capital actual Arrecife, 10 visita la antigua capital Teguise. Hay PDFs por zona: Costa Teguise, Playa Blanca, Puerto del Carmen, Haría y Tinajo.',
            ],
            [
                'title' => 'Taxi - Recomendación de rutas',
                'content' => 'Si el cliente pide una excursión en taxi o pregunta qué ruta hacer, preguntar desde dónde sale: Playa Blanca, Puerto del Carmen, Costa Teguise, Haría o Tinajo. Luego ofrecer rutas Norte o Sur con duración y precio. Si quiere volcanes, recomendar Ruta Sur con Timanfaya, Costa de las Lavas y La Geria. Si quiere norte de Lanzarote, recomendar Jardín del Cactus, Mirador del Río y Jameos del Agua o Cueva de los Verdes. Si quiere senderismo, ofrecer los 10 senderos y pedir zona de recogida.',
            ],
        ];
    }
}
