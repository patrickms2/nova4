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
use App\Enums\AnnouncementType;
use Filament\Tables\Columns\Column;
use App\Models\Announcement;

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
 TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (AnnouncementType $state): string => $state->label())
                    ->color(fn (AnnouncementType $state): string => match ($state) {
                        AnnouncementType::Danger => 'danger',
                        AnnouncementType::Warning => 'warning',
                        AnnouncementType::Info => 'info',
                        AnnouncementType::Success => 'success',
                    }),
                TextColumn::make('user.name')
                    ->label('Creado por')
                    ->searchable(['first_name', 'last_name']),

                IconColumn::make('for_users')
                    ->label('Empleados')
                    ->alignCenter()
                    ->boolean(),

                IconColumn::make('for_clients')
                    ->label('Propietarios')
                    ->alignCenter()
                    ->boolean(),

                TextColumn::make('starts_at')
                    ->label('Visible desde')
                    ->date('d.m.Y')
                    ->sortable(),

            TextColumn::make('expires_at')
                    ->label('Visible hasta')
                    ->date('d.m.Y')
                    ->dateTime()
                    ->sortable()
                    ->suffix(function (Column $column): ?string {
                        /** @var Announcement|null $record */
                        $record = $column->getRecord();

                        return $record instanceof Announcement && $record->isExpiredByDate()
                            ? __('announcements::filament.table.expired')
                            : null;
                    })
                    ->color(function (Column $column): ?string {
                        /** @var Announcement|null $record */
                        $record = $column->getRecord();

                        return $record instanceof Announcement && $record->isExpiredByDate()
                            ? 'danger'
                            : null;
                    }), 
               

   
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
