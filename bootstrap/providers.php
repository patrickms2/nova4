<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\AppPanelProvider::class,
    App\Providers\Filament\PortalPanelProvider::class,
    App\Providers\Filament\CommunityPanelProvider::class,
    App\Providers\Filament\ServersPanelProvider::class,

    App\Providers\Filament\FactPanelProvider::class,
    App\Providers\Filament\DomoticsPanelProvider::class,
    App\Providers\Filament\KnowledgeBasePanelProvider::class,
    App\Providers\VoltServiceProvider::class,
    \Aryxs3m\LaravelHoas\Providers\HomeAssistantProvider::class,
];
