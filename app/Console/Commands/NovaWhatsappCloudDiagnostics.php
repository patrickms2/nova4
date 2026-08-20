<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class NovaWhatsappCloudDiagnostics extends Command
{
    protected $signature = 'nova:whatsapp-cloud-diagnostics';

    protected $description = 'Check Meta WhatsApp Cloud IDs and token without printing secrets';

    public function handle(): int
    {
        $accessToken = (string) config('services.nova.whatsapp_access_token');
        $phoneNumberId = (string) config('services.nova.whatsapp_phone_number_id');
        $businessAccountId = (string) config('services.nova.whatsapp_business_account_id');

        if ($accessToken === '' || $phoneNumberId === '' || $businessAccountId === '') {
            $this->error('Missing WhatsApp Cloud .env values.');

            return self::FAILURE;
        }

        $phoneResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get("https://graph.facebook.com/v19.0/{$phoneNumberId}", [
                'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status',
            ]);

        $wabaResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get("https://graph.facebook.com/v19.0/{$businessAccountId}", [
                'fields' => 'id,name,currency,timezone_id',
            ]);

        $this->table(
            ['Check', 'Status', 'OK', 'Response'],
            [
                [
                    'Phone Number',
                    $phoneResponse->status(),
                    $phoneResponse->successful() ? 'yes' : 'no',
                    json_encode($phoneResponse->json(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
                [
                    'WhatsApp Business Account',
                    $wabaResponse->status(),
                    $wabaResponse->successful() ? 'yes' : 'no',
                    json_encode($wabaResponse->json(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
        );

        return $phoneResponse->successful() && $wabaResponse->successful()
            ? self::SUCCESS
            : self::FAILURE;
    }
}
