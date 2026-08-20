<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NovaAiKnowledge;
use App\Models\NovaAiProfile;
use App\Models\NovaBusiness;
use Illuminate\Console\Command;

final class NovaSeedSirvoRestaurantKnowledge extends Command
{
    protected $signature = 'nova:seed-sirvo-restaurant-knowledge {--business=sirvo}';

    protected $description = 'Seed Nova AI knowledge fragments for a generic Sirvo restaurant assistant';

    public function handle(): int
    {
        $business = NovaBusiness::query()
            ->where('slug', $this->option('business'))
            ->orWhere('name', 'like', '%Sirvo%')
            ->orWhere('business_type', 'restaurant')
            ->first();

        if (! $business) {
            $this->error('No Nova restaurant business found.');

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
                        'source' => 'manual_restaurant_seed',
                        'domain' => 'restaurant_booking',
                    ],
                ],
            );
        }

        $this->info('Sirvo restaurant AI knowledge fragments seeded for Nova.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{title:string, content:string}>
     */
    private function fragments(): array
    {
        return [
            [
                'title' => 'Restaurante - Reservas',
                'content' => 'Para reservar restaurante, pedir al cliente día, hora, número de personas y nombre. Si ya indica esos datos, confirmar que se comprobará disponibilidad. Si falta algún dato, pedirlo con una pregunta corta y clara. Para grupos grandes, alergias o preferencias especiales, preguntar antes de cerrar la reserva.',
            ],
            [
                'title' => 'Restaurante - Información general',
                'content' => 'El asistente puede ayudar con información del restaurante, disponibilidad, reservas de mesa, horarios, número de personas, preferencias, alergias y cambios de reserva. Si el cliente pregunta por comer, cenar, mesa o restaurante, orientar hacia reserva y pedir datos mínimos.',
            ],
            [
                'title' => 'Restaurante - CTA comercial',
                'content' => 'Cuando el cliente pregunte por restaurante o comida, responder de forma breve y comercial: puedo ayudarte a reservar mesa. Indica día, hora y número de personas. Si quiere recomendaciones, ofrecer opciones según tipo de comida, zona, horario o si viene de una visita turística.',
            ],
        ];
    }
}
