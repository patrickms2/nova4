<?php

namespace App\Filament\App\Resources\Announcements\Tables;


use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->tooltip(function ($record) {
                        return new HtmlString($record->content);
                    })
                    ->searchable()
                    ->label('Titulo'),

                TextColumn::make('user.name')
                    ->label('Creado por')
                    ->searchable(['first_name', 'last_name']),

                IconColumn::make('for_users')
                    ->label('Empleados')
                    ->alignCenter()
                    ->boolean(),

                IconColumn::make('for_clients')
                    ->label('Taxistas')
                    ->alignCenter()
                    ->boolean(),

                TextColumn::make('starts_at')
                    ->label('Visible desde')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Visible hasta')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('users_count')
                    ->alignRight()
                    ->counts('users')
                    ->badge()
                    ->label('Usuarios destinatarios'),

                TextColumn::make('departments_count')
                    ->alignRight()
                    ->counts('departments')
                    ->badge()
                    ->label('Departamentos destinatarios'),
               

            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
