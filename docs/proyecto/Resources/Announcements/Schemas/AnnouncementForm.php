<?php

namespace App\Filament\App\Resources\Announcements\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->columnSpanFull()
                    ->label('Titulo')
                    ->required(),

                RichEditor::make('content')
                    ->required()
                    ->extraInputAttributes([
                        'style' => 'min-height: 200px',
                    ])
                    ->label('Contenido')
                    ->columnSpanFull(),

                Toggle::make('for_users')
                    ->inline(false)
                    ->label('Para empleados')
                    ->hint('Mostrar este aviso a los empleados (usuarios internos).')
                    ->default(true),

                Toggle::make('for_clients')
                    ->inline(false)
                    ->label('Para taxistas')
                    ->hint('Mostrar este aviso a los taxistas (portal).')
                    ->default(false),

                MultiSelect::make('users')
                    ->relationship('users', 'name')
                    ->label('Usuarios')
                    ->helperText('Selecciona empleados o taxistas específicos. Dejar vacío para enviar a todos.')
                    ->preload(),

                MultiSelect::make('booking_department_ids')
                    ->relationship('bookingDepartments', 'name')
                    ->label('Departamentos')
                    ->helperText('Selecciona departamentos para aplicar este aviso. Dejar vacío para no restringir.' )
                    ->preload(),

                DatePicker::make('starts_at')
                    ->required()
                    ->label('Fecha de inicio')
                    ->default(now()),

                DatePicker::make('ends_at')
                    ->after('starts_at')
                    ->required()
                    ->label('End date')
                    ->default(now()->addDays(2)),

            ]);
    }
}
