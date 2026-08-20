<?php

namespace App\Filament\App\NovaHub\Resources\NovaWhatsappChannels;

use App\Filament\App\NovaHub\Resources\NovaWhatsappChannels\Pages\CreateNovaWhatsappChannel;
use App\Filament\App\NovaHub\Resources\NovaWhatsappChannels\Pages\EditNovaWhatsappChannel;
use App\Filament\App\NovaHub\Resources\NovaWhatsappChannels\Pages\ListNovaWhatsappChannels;
use App\Filament\App\NovaHub\Resources\NovaWhatsappChannels\Schemas\NovaWhatsappChannelForm;
use App\Filament\App\NovaHub\Resources\NovaWhatsappChannels\Tables\NovaWhatsappChannelsTable;
use App\Models\NovaWhatsappChannel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NovaWhatsappChannelResource extends Resource
{
    protected static ?string $model = NovaWhatsappChannel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Nova Hub';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?string $navigationLabel = 'WhatsApp';

    protected static ?int $navigationSort = 3;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
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
        return [
            'index' => ListNovaWhatsappChannels::route('/'),
            'create' => CreateNovaWhatsappChannel::route('/create'),
            'edit' => EditNovaWhatsappChannel::route('/{record}/edit'),
        ];
    }
}
