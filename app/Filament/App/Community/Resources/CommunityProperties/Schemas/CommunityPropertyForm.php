<?php

namespace App\Filament\App\Community\Resources\CommunityProperties\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommunityPropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([Section::make('Propiedad')->schema([
                Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload()->required(),                        
                Select::make('owner_id')
                            ->label('Propietario')
                            ->relationship('owners', 'email')
                            ->searchable()
                            ->preload()
                            ->required(), 
                            TextInput::make('name')->label('Nombre')->required(), TextInput::make('unit_reference')->label('Referencia / unidad')->required(), TextInput::make('slug')->label('Identificador')->required()->unique(ignoreRecord: true), TextInput::make('address')->label('Dirección')->columnSpanFull(), Toggle::make('is_active')->label('Activa')->default(true)])->columns(2)]);
    }
}
