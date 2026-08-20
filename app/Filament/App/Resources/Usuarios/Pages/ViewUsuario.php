<?php

namespace App\Filament\App\Resources\Usuarios\Pages;

use App\Filament\App\Resources\Usuarios\UsuariosResource;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema as Infolist;

class ViewUsuario extends ViewRecord
{
    protected static string $resource = UsuariosResource::class;

    //protected string $view = 'filament.clusters.taxistas.usuarios.pages.view';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $schema): Infolist
    {

        return $schema
            ->record($this->record)
            ->components([
                Section::make('cabecera')
                    ->extraAttributes(['class' => 'bg-white-100 text-sm',])
                    ->columnSpanFull()
                    ->schema([
                        Flex::make([
                            Grid::make(4)
                                ->schema([
                                    Group::make([
                                        TextEntry::make('nombre')
                                            ->label('Nombre 22'),
                                        TextEntry::make('licencia'),
                                    ]),
                                    Group::make([
                                        TextEntry::make('email'),
                                        TextEntry::make('tel_fijo'),
                                    ]),
                                    Group::make([
                                        TextEntry::make('municipio.nombre')
                                            ->label('Municipio'),
                                        TextEntry::make('estado.nombre')
                                            ->label('Estado'),
                                    ]),
                                    Group::make([
                                        TextEntry::make('municipio.nombre')
                                            ->label('Municipio'),
                                        TextEntry::make('estado.nombre')
                                            ->label('Estado'),
                                    ]),
                                ]),
                        ])->from('lg'),
                    ])
                    ->collapsed(false),
            ]);
    }

    public function infolist2(Infolist $schema): Infolist
    {

        return $schema
            ->components([
                Section::make('cabecera')
                    ->extraAttributes(['class' => 'bg-white-100 text-sm',])
                    ->columnSpanFull()
                    ->schema([
                        Flex::make([
                            Grid::make(4)
                                ->schema([
                                    Group::make([
                                        TextEntry::make('nombre')
                                            ->label('Nombre 22'),
                                        TextEntry::make('licencia'),
                                    ]),
                                    Group::make([
                                        TextEntry::make('email'),
                                        TextEntry::make('tel_fijo'),
                                    ]),
                                    Group::make([
                                        TextEntry::make('municipio.nombre')
                                            ->label('Municipio'),
                                        TextEntry::make('estado.nombre')
                                            ->label('Estado'),
                                    ]),
                                    Group::make([
                                        TextEntry::make('municipio.nombre')
                                            ->label('Municipio'),
                                        TextEntry::make('estado.nombre')
                                            ->label('Estado'),
                                    ]),
                                ]),
                        ])->from('lg'),
                    ]),
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('resumen')->schema([
                            Group::make([
                                TextEntry::make('nombre')
                                    ->label('Nombre 22'),
                                TextEntry::make('licencia'),
                            ]),
                        ]),
                        Tabs\Tab::make('citas')->schema([


                        ]),
                        Tabs\Tab::make('documentos')->schema([]),
                        Tabs\Tab::make('tickets')->schema([]),
                        Tabs\Tab::make('taxis')->schema([]),
                    ])
            ]);
    }
}
