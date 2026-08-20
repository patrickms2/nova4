<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\RelationManagers;

use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\AccessGrantResource;
use App\Models\AccessGrant;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AccessGrantsRelationManager extends RelationManager
{
    protected static string $relationship = 'accessGrants';

    protected static ?string $title = 'Acceso y credenciales';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['person', 'credentials', 'accessPoints']))
            ->columns([
                TextColumn::make('name')->label('Permiso')->searchable(),
                TextColumn::make('person.display_name')->label('Persona')->placeholder('Sin persona'),
                TextColumn::make('credential')->label('Credencial')->state(fn (AccessGrant $record): string => $record->credentials->first()?->maskedValue() ?? (filled($record->pin) ? 'PIN '.Str::mask($record->pin, '•', 0, max(Str::length($record->pin) - 2, 0)) : '—'))->fontFamily('mono'),
                TextColumn::make('accessPoints.name')->label('Puntos de acceso')->badge()->limitList(4),
                TextColumn::make('valid_from')->label('Desde')->dateTime('d M Y H:i'),
                TextColumn::make('valid_until')->label('Hasta')->dateTime('d M Y H:i'),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->recordActions([
                Action::make('view')->label('Abrir permiso')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (AccessGrant $record): string => AccessGrantResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
