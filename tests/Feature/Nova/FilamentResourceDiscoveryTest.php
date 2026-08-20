<?php

declare(strict_types=1);

namespace Tests\Feature\Nova;

use App\Support\Nova\FilamentResourceDiscovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class FilamentResourceDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_parses_filament_resource_metadata_without_booting_the_resource(): void
    {
        $path = app_path('Filament/Test/Resources/FakeIncidentResource.php');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, <<<'PHP'
<?php
namespace App\Filament\Test\Resources;

use App\Models\Incident;
use Filament\Resources\Resource;

class FakeIncidentResource extends Resource
{
    protected static string $model = Incident::class;
    protected static ?string $navigationGroup = 'Mantenimiento';
    protected static ?string $navigationLabel = 'Incidencias';
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?int $navigationSort = 30;
}
PHP);

        try {
            $metadata = app(FilamentResourceDiscovery::class)->inspectFile($path);

            $this->assertSame('App\\Filament\\Test\\Resources\\FakeIncidentResource', $metadata['class_name']);
            $this->assertSame('App\\Models\\Incident', $metadata['model_class']);
            $this->assertSame('Mantenimiento', $metadata['navigation_group']);
            $this->assertSame('Incidencias', $metadata['navigation_label']);
            $this->assertSame(30, $metadata['navigation_sort']);
        } finally {
            File::deleteDirectory(app_path('Filament/Test'));
        }
    }
}
