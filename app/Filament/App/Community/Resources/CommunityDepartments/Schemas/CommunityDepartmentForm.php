<?php

namespace App\Filament\App\Community\Resources\CommunityDepartments\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommunityDepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([Section::make('Departamento')->schema([Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload(), TextInput::make('name')->label('Nombre')->required(), TextInput::make('slug')->label('Código')->required()->unique(ignoreRecord: true), ColorPicker::make('color')->label('Color'), Textarea::make('description')->label('Descripción')->columnSpanFull(), Select::make('employees')->label('Empleados')->relationship('employees', 'name')->multiple()->searchable()->preload()->columnSpanFull(), Toggle::make('is_active')->label('Activo')->default(true)])->columns(2)]);
    }
}
