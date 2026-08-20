<?php

namespace Tests\Feature;

use Tests\TestCase;

class ServerExternalSyncActionsTest extends TestCase
{
    public function test_server_resource_contains_external_sync_actions(): void
    {
        $source = file_get_contents(app_path('Filament/Resources/ServerResource.php'));

        $this->assertStringContainsString("Action::make('registerExternalSources')", $source);
        $this->assertStringContainsString("Action::make('syncExternalSources')", $source);
        $this->assertStringContainsString("Action::make('fullSyncExternalSources')", $source);
        $this->assertStringContainsString('ExternalSourceRegistrar', $source);
        $this->assertStringContainsString('ExternalSourceSynchronizer', $source);
        $this->assertStringContainsString("->where('status', 'active')", $source);
    }
}
