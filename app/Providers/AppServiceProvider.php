<?php

namespace App\Providers;

use App\Contracts\PaymentProvider;
use App\Contracts\PricingStrategyInterface;
use App\Domain\Nova\Missions\Executors;
use App\Domain\Staff\AiWorkReportSummarizer;
use App\Domain\Staff\Contracts\WorkReportSummarizerContract;
use App\Services\AeatClient;
use App\Services\Domotics\DeviceAdapterInterface;
use App\Services\Domotics\DummyAdapter;
use App\Services\Domotics\IkeaHomeAdapter;
use App\Services\Domotics\ShellCommandAdapter;
use App\Services\Pricing\GlobalPricingStrategy;
use App\Services\Przelewy24Service;
use App\Services\Staff\StaffApiClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PricingStrategyInterface::class, function () {
            $key = config('rental.pricing_strategy', 'global');
            $strategies = config('rental.pricing_strategies', []);
            $concrete = $strategies[$key] ?? GlobalPricingStrategy::class;

            return new $concrete;
        });

        $this->app->bind(PaymentProvider::class, function () {
            return new Przelewy24Service([
                'merchant_id' => config('services.przelewy24.merchant_id', 0),
                'pos_id' => config('services.przelewy24.pos_id'),
                'crc' => config('services.przelewy24.crc', ''),
                'api_key' => config('services.przelewy24.api_key', ''),
                'sandbox' => config('services.przelewy24.sandbox', true),
                'app_url' => config('app.url'),
                'webhook_path' => '/api/payment/webhook',
                'return_path' => '/rezerwacja/status',
            ]);
        });

        $this->app->singleton(AeatClient::class, function () {
            return new AeatClient(
                config('verifactu.aeat.cert_path', storage_path('certificates/aeat.pfx')),
                config('verifactu.aeat.cert_password'),
                (bool) config('verifactu.aeat.production', false),
                (bool) config('verifactu.verifactu_mode', true),
            );
        });

        $this->app->singleton(\Squareetlabs\VeriFactu\Services\AeatClient::class, AeatClient::class);

        $this->app->bind(DeviceAdapterInterface::class, function () {
            return match (config('domotics.adapter')) {
                'ikea' => new IkeaHomeAdapter,
                'shell' => new ShellCommandAdapter,
                default => new DummyAdapter,
            };
        });

        $this->app->bind(WorkReportSummarizerContract::class, AiWorkReportSummarizer::class);

        $this->app->singleton(StaffApiClient::class, function () {
            return StaffApiClient::fromConfig();
        });

        $this->app->singleton(Executors\MissionStepExecutor::class, function ($app) {
            return new Executors\CompositeMissionStepExecutor([
                new Executors\WebsiteDiscoveryExecutor,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('verifactu', function () {
            return Limit::perMinute(30)->by('verifactu');
        });

        if (str_contains(request()->getHost(), 'ngrok-free.app')) {
            URL::forceScheme('https');
        }
        if (str_contains(request()->getHost(), 'trycloudflare.com')) {
            URL::forceScheme('https');
        }

    }
}
