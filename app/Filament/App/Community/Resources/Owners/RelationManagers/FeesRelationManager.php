<?php

namespace App\Filament\App\Community\Resources\Owners\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
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
class FeesRelationManager extends RelationManager
{
    protected static string $relationship = 'communityFees';

    protected static ?string $title = 'Cuotas';

    public function form(Schema $s): Schema
    {
        return $s->components([Select::make('community_id')->relationship('community', 'name')->required(), Select::make('property_id')->relationship('properties', 'name')->required(), TextInput::make('concept')->label('Concepto')->required(), DatePicker::make('period')->label('Periodo')->required(), TextInput::make('amount')->label('Importe')->numeric()->prefix('€')->required(), DatePicker::make('due_date')->label('Vencimiento'), Select::make('status')->options(['pending' => 'Pendiente', 'paid' => 'Pagada', 'overdue' => 'Vencida', 'cancelled' => 'Cancelada'])->default('pending')]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('period')->label('Periodo')->date('m/Y'), TextColumn::make('concept')->label('Concepto'), TextColumn::make('property.name')->label('Propiedad'), TextColumn::make('amount')->label('Importe')->money('EUR'), TextColumn::make('status')->label('Estado')->badge()])->headerActions([CreateAction::make()]);
    }
}
