<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela payment_settings - konfiguracja bramki platnosci.
 *
 * Credentials (merchant_id, crc, api_key) sa szyfrowane w DB.
 * Kazdy tenant moze miec wlasne klucze - bez deploya .env.
 *
 * KML-0066
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $t) {
            $t->id();

            // Opcjonalny tenant_id dla multi-tenant
            $t->string('tenant_id')->nullable()->index();

            // Provider type (przelewy24, stripe, payu, etc.)
            $t->string('provider')->default('przelewy24');

            // Przelewy24 credentials (encrypted via Laravel Crypt)
            $t->text('merchant_id')->nullable()->comment('Encrypted');
            $t->text('pos_id')->nullable()->comment('Encrypted, defaults to merchant_id');
            $t->text('crc')->nullable()->comment('Encrypted');
            $t->text('api_key')->nullable()->comment('Encrypted');

            // Environment
            $t->boolean('sandbox')->default(true);
            $t->boolean('enabled')->default(false);

            // URLs (auto-generated, shown in UI for copy-paste to P24 panel)
            $t->string('webhook_path')->default('/api/payment/webhook');
            $t->string('return_path')->default('/rezerwacja/status');

            // Metadata
            $t->json('meta')->nullable();

            $t->timestamps();

            // Unique constraint per tenant + provider
            $t->unique(['tenant_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
