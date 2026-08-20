<?php

namespace App\Filament\App\Facturacion\Resources\NoteResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Contenido')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('content')
                            ->label('Contenido')
                            ->columnSpanFull(),
                        Select::make('project_id')
                            ->label('Proyecto')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('folder_id')
                            ->label('Carpeta')
                            ->relationship('folder', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('created_by')
                            ->label('Creado por')
                            ->relationship('createdBy', 'name')
                            ->searchable()
                            ->preload()
                            ->default(auth()->id())
                            ->required(),
                        TagsInput::make('tags')
                            ->label('Etiquetas')
                            ->placeholder('Añadir etiqueta')
                            ->splitKeys(['Enter', ',']),
                        Toggle::make('is_pinned')
                            ->label('Fijar')
                            ->live()
                            ->hint('Destaca la nota en el dashboard'),
                    ]),
            ]);
    }
}
