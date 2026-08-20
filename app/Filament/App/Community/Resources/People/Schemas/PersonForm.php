<?php

namespace App\Filament\App\Community\Resources\People\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PersonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([Section::make('Identidad')->schema([TextInput::make('first_name')->label('Nombre')->required(), TextInput::make('last_name')->label('Apellidos'), TextInput::make('display_name')->label('Nombre visible')->required(), TextInput::make('email')->email(), TextInput::make('phone')->tel()->label('Teléfono'), TextInput::make('document_number')->label('Documento'), Select::make('user_id')->label('Cuenta NOVA')->relationship('user', 'name')->searchable()->preload()])->columns(2)]);
    }
}
