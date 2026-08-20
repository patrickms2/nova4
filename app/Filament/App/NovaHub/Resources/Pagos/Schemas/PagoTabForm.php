<?php

declare(strict_types=1);

 namespace App\Filament\App\NovaHub\Resources\Pagos\Schemas;

use App\Enums\PagoEstado;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class PagoTabForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->autofocus()
                    ->required(),
                Textarea::make('description')
                    ->autosize()
                    ->rows(4)
                    ->columnSpanFull(),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('deadline'),
                Select::make('status')
                    ->options(PagoEstado::class)
                    ->required()
                    ->default('in_progress'),
            ]);
    }
}
