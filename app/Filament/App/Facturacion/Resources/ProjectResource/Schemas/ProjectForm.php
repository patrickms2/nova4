<?php

namespace App\Filament\App\Facturacion\Resources\ProjectResource\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Información básica')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                        Select::make('parent_id')
                            ->label('Proyecto padre')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Sin proyecto padre'),
                        Select::make('project_category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('created_by')
                            ->label('Creado por')
                            ->relationship('createdBy', 'name')
                            ->searchable()
                            ->preload()
                            ->default(auth()->id())
                            ->required(),
                    ]),
                Section::make('Configuración y fechas')
                    ->columns(3)
                    ->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'planning' => 'Planificación',
                                'active' => 'Activo',
                                'on_hold' => 'En pausa',
                                'archived' => 'Archivado',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->default('planning')
                            ->required(),
                        Select::make('phase')
                            ->label('Fase')
                            ->options([
                                'planning' => 'Planificación',
                                'development' => 'Desarrollo',
                                'testing' => 'Pruebas',
                                'completed' => 'Completado',
                            ])
                            ->default('planning'),
                        ColorPicker::make('color')
                            ->label('Color'),
                        TextInput::make('icon')
                            ->label('Icono')
                            ->maxLength(50),
                        DatePicker::make('start_date')
                            ->label('Fecha inicio')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('end_date')
                            ->label('Fecha fin')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('start_date'),
                        Toggle::make('is_public')
                            ->label('Público'),
                    ]),
            ]);
    }
}
