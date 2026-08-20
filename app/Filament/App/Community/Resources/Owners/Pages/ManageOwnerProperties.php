<?php

namespace App\Filament\App\Community\Resources\Owners\Pages;

use App\Filament\App\Community\Resources\Owners\OwnerResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
class ManageOwnerProperties extends ManageRelatedRecords
{
    protected static string $resource = OwnerResource::class;

    protected static string $relationship = 'properties';

    protected static ?string $navigationLabel = 'Propiedades';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'created_by' => auth()->id()])];
    }
    public function form(Schema $s): Schema
    {
        return $s->components([
             Select::make('person_id')->label('Propietario')->relationship('owner', 'display_name')->searchable()->preload()->required(),
                          Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload(),

        TextInput::make('name')->required(), TextInput::make('unit_reference')->label('Unidad'), TextInput::make('address')->label('Dirección'), TextInput::make('slug')->required(), TextInput::make('timezone')->default('Atlantic/Canary')]);
    }
    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Propiedad')->searchable(),
                        TextColumn::make('community.name')->label('Comunidad')->searchable(),

            TextColumn::make('unit_reference')->label('Unidad'), TextColumn::make('community.name')->label('Comunidad'), TextColumn::make('address')->label('Dirección')])
             ->recordActions([
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
