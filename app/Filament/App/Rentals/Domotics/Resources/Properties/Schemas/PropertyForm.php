<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Properties\Schemas;

use App\Models\User;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos básicos')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Villa Norte')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->placeholder('villa-norte')
                            ->helperText('Usado en la URL del tenant.')
                            ->required()
                            ->maxLength(255)
                            ->unique('properties', 'slug',),
                        Textarea::make('address')
                            ->label('Dirección')
                            ->rows(3)
                            ->maxLength(1000)
                            ->nullable(),
                        Select::make('timezone')
                            ->label('Zona horaria')
                            ->options(collect(\DateTimeZone::listIdentifiers())->mapWithKeys(fn ($tz) => [$tz => $tz]))
                            ->searchable()
                            ->default('Atlantic/Canary')
                            ->required(),
                        Select::make('owner_id')
                            ->label('Propietario')
                            ->relationship('owner', 'email')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
