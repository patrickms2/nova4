<?php

namespace App\Filament\App\Community\Resources\Employees\Pages;

use App\Filament\App\Community\Resources\Employees\EmployeeResource;

use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
class ManageEmployeeDepartments extends ManageRelatedRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'communityDepartments';

    protected static ?string $navigationLabel = 'Departamentos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected function getHeaderActions(): array
    {
        return [
            AttachAction::make()->preloadRecordSelect()
            ];
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Departamento'), TextColumn::make('community.name')->label('Comunidad')])
         ->recordActions([
            DetachAction::make(),
            ViewAction::make(),
            EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
