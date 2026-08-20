<?php

namespace App\Filament\App\Facturacion\Resources\TaskResource\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Información básica')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                        Select::make('project_id')
                            ->label('Proyecto')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Seleccionar proyecto'),
                        Select::make('assigned_to')
                            ->label('Asignado a')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('task_category_id')
                            ->label('Categoría')
                            ->relationship('taskCategory', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
                Section::make('Estado y planificación')
                    ->columns(3)
                    ->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'in_progress' => 'En progreso',
                                'completed' => 'Completada',
                                'done' => 'Hecho',
                                'cancelled' => 'Cancelada',
                            ])
                            ->default('pending')
                            ->required(),
                        Select::make('priority')
                            ->label('Prioridad')
                            ->options([
                                'low' => 'Baja',
                                'medium' => 'Media',
                                'high' => 'Alta',
                                'critical' => 'Crítica',
                            ])
                            ->default('medium')
                            ->required(),
                        TextInput::make('type')
                            ->label('Tipo')
                            ->maxLength(50),
                        DatePicker::make('due_date')
                            ->label('Fecha límite')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->nullable(),
                        Toggle::make('is_completed')
                            ->label('Completada')
                            ->live()
                            ->hint('Marca la tarea como finalizada'),
                    ]),
            ]);
    }
}
