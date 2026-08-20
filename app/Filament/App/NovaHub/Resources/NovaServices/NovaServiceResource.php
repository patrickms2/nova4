<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaServices;

use App\Filament\App\NovaHub\Resources\NovaServices\Pages\CreateNovaService;
use App\Filament\App\NovaHub\Resources\NovaServices\Pages\EditNovaService;
use App\Filament\App\NovaHub\Resources\NovaServices\Pages\ListNovaServices;
use App\Filament\App\NovaHub\Resources\NovaServices\Schemas\NovaServiceForm;
use App\Filament\App\NovaHub\Resources\NovaServiceResource\Tables\NovaServicesTable;
use App\Models\NovaService;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class NovaServiceResource extends Resource
{
    protected static ?string $model = NovaServiceResource::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Nova Hub';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?string $navigationLabel = 'Servicios';

    protected static ?string $modelLabel = 'Servicio';

    protected static ?string $pluralModelLabel = 'Servicios';

    protected static ?int $navigationSort = 4;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return NovaServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NovaServicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNovaServices::route('/'),
            'create' => CreateNovaService::route('/create'),
            'edit' => EditNovaService::route('/{record}/edit'),
        ];
    }
}
