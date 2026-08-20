<?php

namespace App\Providers;

use App\Services\McpServerGenerator;
use Illuminate\Support\ServiceProvider;

class McpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(McpServerGenerator::class);
    }

    public function boot(): void
    {
        // Servers are registered in routes/ai.php
    }
}
