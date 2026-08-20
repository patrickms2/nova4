<?php

namespace App\Filament\App\Community\Resources\Owners\Pages;

use App\Filament\App\Community\Resources\Owners\OwnerResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
class ManageOwnerFees extends ManageRelatedRecords
{
    protected static string $resource = OwnerResource::class;

    protected static string $relationship = 'communityFees';

    protected static ?string $navigationLabel = 'Cuotas';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'created_by' => auth()->id()])];
    }
    public function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('community_id')->relationship('community', 'name')->required(), Select::make('property_id')->relationship('property', 'name')->required(), TextInput::make('concept')->label('Concepto')->required(), DatePicker::make('period')->label('Periodo')->required(), TextInput::make('amount')->label('Importe')->numeric()->prefix('€')->required(), DatePicker::make('due_date')->label('Vencimiento'), Select::make('status')->options(['pending' => 'Pendiente', 'paid' => 'Pagada', 'overdue' => 'Vencida'])->default('pending')]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('period')->label('Periodo')->date('m/Y'), TextColumn::make('property.name')->label('Propiedad'), TextColumn::make('concept')->label('Concepto'), TextColumn::make('amount')->label('Importe')->money('EUR'), TextColumn::make('due_date')->label('Vence')->date(), TextColumn::make('status')->label('Estado')->badge()]);
    }
}
