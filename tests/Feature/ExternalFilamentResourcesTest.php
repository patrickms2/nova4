<?php

namespace Tests\Feature;

use App\Filament\Resources\ExternalBookingResource;
use App\Filament\Resources\ExternalCatalogItemResource;
use App\Filament\Resources\ExternalOrderResource;
use App\Filament\Resources\ExternalSourceResource;
use App\Models\ExternalBooking;
use App\Models\ExternalCatalogItem;
use App\Models\ExternalOrder;
use App\Models\ExternalSource;
use Tests\TestCase;

class ExternalFilamentResourcesTest extends TestCase
{
    public function test_external_resources_target_the_expected_models(): void
    {
        $this->assertSame(ExternalSource::class, ExternalSourceResource::getModel());
        $this->assertSame(ExternalCatalogItem::class, ExternalCatalogItemResource::getModel());
        $this->assertSame(ExternalBooking::class, ExternalBookingResource::getModel());
        $this->assertSame(ExternalOrder::class, ExternalOrderResource::getModel());
    }

    public function test_catalog_and_booking_resources_include_source_columns_and_filters(): void
    {
        $catalogSource = file_get_contents(app_path('Filament/Resources/ExternalCatalogItemResource.php'));
        $bookingSource = file_get_contents(app_path('Filament/Resources/ExternalBookingResource.php'));

        foreach (['source_label', 'business_name', 'source_platform', 'server.name'] as $field) {
            $this->assertStringContainsString($field, $catalogSource);
            $this->assertStringContainsString($field, $bookingSource);
        }
    }

    public function test_external_sources_show_target_model_and_sync_actions(): void
    {
        $source = file_get_contents(app_path('Filament/Resources/ExternalSourceResource.php'));

        foreach (['resource_type', 'target_model', 'sync_direction', 'capability'] as $field) {
            $this->assertStringContainsString($field, $source);
        }

        $this->assertStringContainsString('syncExternalSource', $source);
        $this->assertStringContainsString('fullSyncExternalSource', $source);
        $this->assertStringContainsString('ExternalSourceSynchronizer', $source);
        $this->assertStringContainsString('RequestException', $source);
        $this->assertStringContainsString('->danger()', $source);
    }

    public function test_external_booking_and_catalog_resources_show_source_target_model(): void
    {
        $catalogSource = file_get_contents(app_path('Filament/Resources/ExternalCatalogItemResource.php'));
        $bookingSource = file_get_contents(app_path('Filament/Resources/ExternalBookingResource.php'));

        // Catalog items use the external source relationship for resource/type context.
        foreach (['externalSource.resource_type', 'externalSource.target_model'] as $field) {
            $this->assertStringContainsString($field, $catalogSource);
        }

        // Bookings store resource/model directly on the record (and fall back to the relation).
        foreach (['resource_type', 'target_model'] as $field) {
            $this->assertStringContainsString($field, $bookingSource);
        }
    }
}
