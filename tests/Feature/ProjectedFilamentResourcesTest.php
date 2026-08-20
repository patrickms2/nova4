<?php

namespace Tests\Feature;

use App\Filament\Resources\TourAdmin\Resources\TourResource;
use App\Models\Tour;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProjectedFilamentResourcesTest extends TestCase
{
    public function test_tour_resource_is_registered_in_admin_panel(): void
    {
        $this->assertSame(Tour::class, TourResource::getModel());
        $this->assertTrue(Route::has('filament.admin.resources.tours.index'));
    }

    public function test_tour_forms_use_real_tour_name_column(): void
    {
        foreach ([
                     app_path('Filament/Resources/TourResource.php'),
                     app_path('Filament/TourAdmin/Resources/TourResource.php'),
                 ] as $file) {
            $source = file_get_contents($file);

            $this->assertStringContainsString("TextInput::make('tour_name')", $source, $file);
            $this->assertStringNotContainsString("TextInput::make('name')", $source, $file);
        }
    }

    public function test_restaurant_forms_use_real_restaurant_name_column(): void
    {
        foreach ([
                     app_path('Filament/Resources/RestaurantResource.php'),
                     app_path('Filament/RestaurantAdmin/Resources/RestaurantResource.php'),
                 ] as $file) {
            $source = file_get_contents($file);

            $this->assertStringContainsString("TextInput::make('restaurant_name')", $source, $file);
            $this->assertStringNotContainsString("TextInput::make('name')", $source, $file);
        }
    }

    public function test_native_resource_forms_include_source_and_type(): void
    {
        foreach ($this->nativeResourceFiles() as $file) {
            $source = file_get_contents($file);

            $this->assertStringContainsString("Placeholder::make('source_label')", $source, $file);
            $this->assertStringContainsString("Placeholder::make('resource_type')", $source, $file);
            $this->assertStringContainsString('External Source', $source, $file);
        }
    }

    public function test_native_resources_include_source_columns_or_filters(): void
    {
        foreach ($this->nativeResourceFiles() as $file) {
            $source = file_get_contents($file);
            $this->assertStringContainsString('externalSyncMappings.source_label', $source, $file);
            $this->assertStringContainsString('externalSyncMappings.resource_type', $source, $file);
            $this->assertStringContainsString('Source', $source, $file);
        }
    }

    public function test_external_catalog_resource_includes_resource_type(): void
    {
        $source = file_get_contents(app_path('Filament/Resources/ExternalCatalogItemResource.php'));

        $this->assertStringContainsString('resource_type', $source);
        $this->assertStringContainsString('source_label', $source);
    }

    /**
     * @return list<string>
     */
    private function nativeResourceFiles(): array
    {
        return [
            app_path('Filament/Resources/HotelResource.php'),
            app_path('Filament/HotelAdmin/Resources/HotelResource.php'),
            app_path('Filament/Resources/RestaurantResource.php'),
            app_path('Filament/RestaurantAdmin/Resources/RestaurantResource.php'),
            app_path('Filament/Resources/TourResource.php'),
            app_path('Filament/TourAdmin/Resources/TourResource.php'),
            app_path('Filament/Resources/TaxiServiceResource.php'),
        ];
    }
}
