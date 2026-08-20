<?php

namespace Database\Seeders;

use App\Models\NovaIntentRule;
use Illuminate\Database\Seeder;

class NovaIntentRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Migrates hardcoded keywords from NovaConversationDataExtractor to DB.
     * nova_business_id = null → global rule (applies to all businesses).
     */
    public function run(): void
    {
        $rules = [
            // --- Listing trigger keywords (commercial_info include) ---
            [
                'nova_business_id' => null,
                'intent_key' => 'commercial_info',
                'rule_type' => 'include',
                'keywords' => ['listado', 'lista', 'dame', 'muestra', 'hay', 'cuales', 'ver', 'obtener', 'disponible', 'activos', 'activas', 'activo'],
                'description' => 'Palabras que indican petición de listado',
                'priority' => 10,
            ],

            // --- Count / total trigger keywords ---
            [
                'nova_business_id' => null,
                'intent_key' => 'commercial_info_count',
                'rule_type' => 'include',
                'keywords' => ['cuántos', 'cuantos', 'cuántas', 'cuantas', 'total de', 'el total', 'número de', 'numero de', 'dime el total', 'dame el total'],
                'description' => 'Palabras que indican petición de recuento',
                'priority' => 10,
            ],

            // --- Service topics → commercial not system_info ---
            [
                'nova_business_id' => null,
                'intent_key' => 'commercial_info',
                'rule_type' => 'system_topic',
                'keywords' => ['restaurante', 'restaurantes', 'bodega', 'bodegas', 'taxi', 'taxis', 'traslado', 'transfer', 'producto', 'productos', 'aloe', 'vinoterapia', 'lanzaloe', 'geria', 'vino', 'vinos', 'tienda', 'comer', 'cenar', 'reservar', 'reserva', 'mesa', 'visita', 'visitas', 'tour', 'excursion', 'excursión', 'actividad', 'hotel', 'hoteles', 'alojamiento', 'hospedaje', 'ruta', 'rutas', 'itinerario'],
                'description' => 'Temas de servicio que clasifican la query como commercial_info y no system_info',
                'priority' => 10,
            ],

            // --- System info keywords ---
            [
                'nova_business_id' => null,
                'intent_key' => 'system_info',
                'rule_type' => 'include',
                'keywords' => ['agente', 'agentes', 'servidor', 'servidores', 'mcp', 'herramienta', 'herramientas', 'qué puedes', 'que puedes', 'qué sabes', 'que sabes', 'cómo funciona', 'como funciona', 'qué eres', 'que eres'],
                'description' => 'Palabras que indican consulta sobre el sistema o agente',
                'priority' => 5,
            ],

            // --- Location stop words (exclude from place detection) ---
            [
                'nova_business_id' => null,
                'intent_key' => 'location_filter',
                'rule_type' => 'exclude',
                'keywords' => ['el', 'la', 'los', 'las', 'un', 'una', 'este', 'tiempo', 'real', 'directo'],
                'description' => 'Palabras genéricas que no son nombres de lugar',
                'priority' => 10,
            ],

            // --- System/brand names (exclude from location detection) ---
            [
                'nova_business_id' => null,
                'intent_key' => 'location_filter',
                'rule_type' => 'system_topic',
                'keywords' => ['taxilanz', 'sirvo', 'lageria', 'geria', 'lanzaloe', 'lanzarote', 'nova', 'mcp'],
                'description' => 'Nombres de marca/sistema que no son lugares geográficos',
                'priority' => 10,
            ],
        ];

        foreach ($rules as $data) {
            NovaIntentRule::updateOrCreate(
                [
                    'nova_business_id' => $data['nova_business_id'],
                    'intent_key' => $data['intent_key'],
                    'rule_type' => $data['rule_type'],
                    'description' => $data['description'],
                ],
                $data
            );
        }

        $this->command->info('NovaIntentRules seeded ('.count($rules).' global rules).');
    }
}
