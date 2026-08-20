<?php

namespace App\Console\Commands;

use App\Models\NovaBusiness;
use App\Models\NovaWhatsappChannel;
use Illuminate\Console\Command;

class NovaRegisterWhatsappCloud extends Command
{
    protected $signature = 'nova:register-whatsapp-cloud
        {--business=Nova : Nova business name}
        {--slug=nova : Nova business slug}
        {--webhook-url=https://taxilanzhrnew.test/api/nova/whatsapp/webhook : Public webhook callback URL}';

    protected $description = 'Register the Meta WhatsApp Cloud channel in Nova using .env values';

    public function handle(): int
    {
        $phoneNumberId = (string) config('services.nova.whatsapp_phone_number_id');
        $businessAccountId = (string) config('services.nova.whatsapp_business_account_id');

        if ($phoneNumberId === '' || $businessAccountId === '') {
            $this->error('Missing NOVA_WHATSAPP_PHONE_NUMBER_ID or NOVA_WHATSAPP_BUSINESS_ACCOUNT_ID in .env');

            return self::FAILURE;
        }

        $business = NovaBusiness::updateOrCreate(
            ['slug' => (string) $this->option('slug')],
            [
                'name' => (string) $this->option('business'),
                'business_type' => 'other',
                'status' => 'active',
                'subscription_amount' => 200,
                'commission_rate' => 10,
            ],
        );

        $channel = NovaWhatsappChannel::updateOrCreate(
            [
                'nova_business_id' => $business->id,
                'provider' => 'meta',
                'phone_number_id' => $phoneNumberId,
            ],
            [
                'name' => 'Nova WhatsApp Cloud',
                'phone_number' => env('NOVA_WHATSAPP_PHONE_NUMBER'),
                'business_account_id' => $businessAccountId,
                'webhook_url' => (string) $this->option('webhook-url'),
                'status' => 'active',
                'credentials' => [
                    'access_token_configured' => config('services.nova.whatsapp_access_token') !== null,
                    'app_secret_configured' => env('NOVA_META_APP_SECRET') !== null,
                ],
                'settings' => [
                    'meta_business_id' => config('services.nova.meta_business_id'),
                    'meta_app_id' => config('services.nova.meta_app_id'),
                    'verify_token_configured' => config('services.nova.whatsapp_verify_token') !== null,
                ],
            ],
        );

        $this->info('WhatsApp Cloud channel registered.');
        $this->line('Business: '.$business->name);
        $this->line('Channel ID: '.$channel->id);
        $this->line('Phone Number ID: '.$channel->phone_number_id);
        $this->line('Webhook URL: '.$channel->webhook_url);

        return self::SUCCESS;
    }
}
