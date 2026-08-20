<?php

declare(strict_types=1);

namespace Tests\Feature\Nova;

use App\Support\Nova\FilamentStructureScanner;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class FilamentStructureScannerTest extends TestCase
{
    public function test_scanner_detects_pages_subnavigation_relations_widgets_and_tabs(): void
    {
        $root = storage_path('framework/testing/nova-filament-structure');
        File::deleteDirectory($root);
        File::ensureDirectoryExists($root.'/Pages');
        File::ensureDirectoryExists($root.'/RelationManagers');
        File::ensureDirectoryExists($root.'/Widgets');

        File::put($root.'/ExampleResource.php', <<<'PHP'
<?php
namespace App\Filament\App\Resources\Examples;

use App\Models\Example;
use Filament\Resources\Resource;
use App\Filament\App\Resources\Examples\Pages\ListExamples;
use App\Filament\App\Resources\Examples\Pages\CalendarExamples;
use App\Filament\App\Resources\Examples\RelationManagers\DocumentsRelationManager;
use App\Filament\App\Resources\Examples\Widgets\ExampleStats;

class ExampleResource extends Resource {
    protected static ?string $model = Example::class;
    protected static ?string $navigationLabel = 'Examples';

    public static function getRecordSubNavigation($page): array {
        return $page->generateNavigationItems([CalendarExamples::class]);
    }

    public static function getRelations(): array {
        return [DocumentsRelationManager::class];
    }

    public static function getWidgets(): array {
        return [ExampleStats::class];
    }

    public static function getPages(): array {
        return [
            'index' => ListExamples::route('/'),
            'calendar' => CalendarExamples::route('/calendar'),
        ];
    }
}
PHP);

        $structure = app(FilamentStructureScanner::class)->scan($root.'/ExampleResource.php');

        $this->assertSame('Examples', $structure['navigation']['label']);
        $this->assertArrayHasKey('index', $structure['pages']);
        $this->assertArrayHasKey('calendar', $structure['pages']);
        $this->assertCount(1, $structure['relations']);
        $this->assertCount(1, $structure['widgets']);
        $this->assertCount(1, $structure['record_subnavigation']);
    }
}
