<?php

namespace App\Filament\App\Resources\Announcements\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use App\Enums\AnnouncementType;
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

                RichEditor::make('body')
                    ->required()
                    ->extraInputAttributes([
                        'style' => 'min-height: 200px',
                    ])
                    ->label('Contenido')
                    ->columnSpanFull(),

                        Select::make('type')
                            ->label('Tipo')
                            ->required()
                            ->options(AnnouncementType::class)
                            ->default('Info'),

                Toggle::make('for_users')
                    ->inline(false)
                    ->label('Para empleados')
                    ->hint('Mostrar este aviso a los empleados (usuarios internos).')
                    ->default(true),

                Toggle::make('for_clients')
                    ->inline(false)
                    ->label('Para propietarios')
                    ->hint('Mostrar este aviso a los propietarios (portal).')
                    ->default(false),

                MultiSelect::make('users')
                    ->relationship('users', 'name')
                    ->label('Usuarios')
                    ->helperText('Selecciona empleados o propietarios específicos. Dejar vacío para enviar a todos.')
                    ->preload(),

                MultiSelect::make('department_ids')
                    ->relationship('departments', 'name')
                    ->label('Departamentos')
                    ->helperText('Selecciona departamentos para aplicar este aviso. Dejar vacío para no restringir.' )
                    ->preload(),

                DatePicker::make('starts_at')
                    ->required()
                    ->label('Fecha de inicio')
                    ->default(now()),

                DatePicker::make('expires_at')
                    ->after('starts_at')
                    ->required()
                    ->label('Fecha de expiración')
                    ->default(now()->addDays(2)),
                   

            ]);
    }
}
