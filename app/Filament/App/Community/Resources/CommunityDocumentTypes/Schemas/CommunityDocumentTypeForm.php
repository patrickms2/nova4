<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommunityDocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([Section::make('Tipo documental')->schema([Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload(), TextInput::make('name')->label('Nombre')->required(), TextInput::make('code')->label('Código')->required(), Textarea::make('description')->label('Descripción')->columnSpanFull(), Toggle::make('requires_expiration')->label('Requiere caducidad'), Toggle::make('is_active')->label('Activo')->default(true)])->columns(2)]);
    }
}
