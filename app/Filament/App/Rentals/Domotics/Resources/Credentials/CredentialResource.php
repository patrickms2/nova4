<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Credentials;

use App\Filament\App\Rentals\Domotics\Resources\Credentials\Pages\CreateCredential;
use App\Filament\App\Rentals\Domotics\Resources\Credentials\Pages\EditCredential;
use App\Filament\App\Rentals\Domotics\Resources\Credentials\Pages\ListCredentials;
use App\Filament\App\Rentals\Domotics\Resources\Credentials\Pages\ViewCredential;
use App\Filament\App\Rentals\Domotics\Resources\Credentials\Schemas\CredentialForm;
use App\Filament\App\Rentals\Domotics\Resources\Credentials\Schemas\CredentialInfolist;
use App\Filament\App\Rentals\Domotics\Resources\Credentials\Tables\CredentialsTable;
use App\Filament\App\Rentals\Rentals;
use App\Models\Credential;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CredentialResource extends Resource
{
    protected static ?string $model = Credential::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;
    protected static ?string $cluster = Rentals::class;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Nova Access';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return CredentialForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CredentialInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CredentialsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCredentials::route('/'),
            'create' => CreateCredential::route('/create'),
            'view' => ViewCredential::route('/{record}'),
            'edit' => EditCredential::route('/{record}/edit'),
        ];
    }
}
