<?php

namespace App\Filament\App\Community\Resources\CommunityDepartments;

use App\Filament\App\Community\Resources\CommunityDepartments\Pages\CreateCommunityDepartment;
use App\Filament\App\Community\Resources\CommunityDepartments\Pages\EditCommunityDepartment;
use App\Filament\App\Community\Resources\CommunityDepartments\Pages\ListCommunityDepartments;
use App\Filament\App\Community\Resources\CommunityDepartments\Pages\ViewCommunityDepartment;
use App\Filament\App\Community\Resources\CommunityDepartments\Schemas\CommunityDepartmentForm;
use App\Filament\App\Community\Resources\CommunityDepartments\Schemas\CommunityDepartmentInfolist;
use App\Filament\App\Community\Resources\CommunityDepartments\Tables\CommunityDepartmentsTable;
use App\Models\CommunityDepartment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommunityDepartmentResource extends Resource
{
    protected static ?string $model = CommunityDepartment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Empresa';

    protected static ?string $navigationLabel = 'Departamentos';

    protected static ?string $modelLabel = 'Departamento';
    protected static ?string $navigationParentGroup = 'Nova Community';
    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CommunityDepartmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommunityDepartmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommunityDepartmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunityDepartments::route('/'),
            'create' => CreateCommunityDepartment::route('/create'),
            'view' => ViewCommunityDepartment::route('/{record}'),
            'edit' => EditCommunityDepartment::route('/{record}/edit'),
        ];
    }
}
