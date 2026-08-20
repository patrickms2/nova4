<?php

namespace App\Filament\Resources\NovaWhatsappChannels;

use App\Filament\Resources\NovaWhatsappChannels\Pages\CreateNovaWhatsappChannel;
use App\Filament\Resources\NovaWhatsappChannels\Pages\EditNovaWhatsappChannel;
use App\Filament\Resources\NovaWhatsappChannels\Pages\ListNovaWhatsappChannels;
use App\Filament\Resources\NovaWhatsappChannels\Schemas\NovaWhatsappChannelForm;
use App\Filament\Resources\NovaWhatsappChannels\Tables\NovaWhatsappChannelsTable;
use App\Models\NovaWhatsappChannel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NovaWhatsappChannelResource extends Resource
{
    protected static ?string $model = NovaWhatsappChannel::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

        protected static string|\UnitEnum|null $navigationGroup = 'IA';
    protected static ?string $navigationParentGroup = 'Nova Hub';
    
    protected static ?string $navigationLabel = 'WhatsApp';
    protected static ?int $navigationSort = 3;
    protected static bool $isScopedToTenant = false;

    public static function form(Form $schema): Form
    {
        return NovaWhatsappChannelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NovaWhatsappChannelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return ['index' => ListNovaWhatsappChannels::route('/'),
            'create' => CreateNovaWhatsappChannel::route('/create'),
            'edit' => EditNovaWhatsappChannel::route('/{record}/edit'),
        ];
    }
}
