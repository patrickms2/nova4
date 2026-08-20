<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\Pagos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class PagoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nombre'),
                TextEntry::make('start_date'),
                TextEntry::make('deadline'),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
