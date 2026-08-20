<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('api_connectors')) {
            return;
        }

        $this->schema()->create('api_connectors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('base_url');
            $table->string('auth_type')->default('none'); // none, api_key, bearer, basic, custom_header
            $table->text('auth_credentials')->nullable(); // Encrypted JSON: {key, value, header_name, username, password}
            $table->text('default_headers')->nullable(); // JSON: {"Content-Type": "application/json", ...}
            $table->unsignedInteger('default_timeout')->default(30);
            $table->boolean('verify_ssl')->default(true);
            $table->text('response_format')->nullable(); // json, xml, text — hint for parsing
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('api_connectors');
    }
};
