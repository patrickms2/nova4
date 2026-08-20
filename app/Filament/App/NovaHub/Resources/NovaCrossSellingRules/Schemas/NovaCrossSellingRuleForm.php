<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\Schemas;

use Filament\Support\Icons\Heroicon;

use App\Models\NovaBusiness;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class NovaCrossSellingRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Cross-Selling Rule Setup')
                    ->persistTab()
                    ->tabs([
                        Tab::make('Básico')
                            ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                            ->schema([
                                Section::make('Regla de sugerencia')
                                    ->description('Cuándo y qué sugiere Nova como servicio complementario.')
                                    ->schema(static::ruleFields())
                                    ->columns(2),
                            ]),

                        Tab::make('Sugerencia')
                            ->icon(Heroicon::OutlinedLightBulb)
                            ->schema([
                                Section::make('Negocio sugerido')
                                    ->description('Qué negocio o servicio recomienda Nova al detectar el intent de activación.')
                                    ->schema(static::suggestionFields())
                                    ->columns(2),

                                Section::make('Condiciones avanzadas')
                                    ->description('Restricciones adicionales de aplicación de la regla.')
                                    ->schema(static::conditionFields())
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /** @return array<int, mixed> */
    protected static function ruleFields(): array
    {
        return [
            Select::make('trigger_intent')
                ->label('Intent que activa la regla')
                ->helperText('Cuando Nova detecte esta intención, sugerirá el negocio configurado.')
                ->options([
                    'restaurant_booking' => 'Reserva restaurante',
                    'winery_visit' => 'Visita bodega / tour',
                    'hotel' => 'Hotel / alojamiento',
                    'product_info' => 'Información producto',
                    'taxi_booking' => 'Reserva taxi / transfer',
                    'commercial_info' => 'Información comercial',
                ])
                ->native(false)
                ->required(),

            ToggleButtons::make('is_active')
                ->label('Estado')
                ->options([
                    '1' => 'Activa',
                    '0' => 'Inactiva',
                ])
                ->colors([
                    '1' => 'success',
                    '0' => 'gray',
                ])
                ->inline()
                ->default('1'),

            Select::make('from_business_id')
                ->label('Negocio origen')
                ->helperText('Negocio desde el que se aplica la regla.')
                ->options(fn (): array => NovaBusiness::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->required(),

            TextInput::make('priority')
                ->label('Prioridad')
                ->helperText('Menor número = mayor prioridad.')
                ->numeric()
                ->default(0),
        ];
    }

    /** @return array<int, mixed> */
    protected static function suggestionFields(): array
    {
        return [
            Select::make('to_business_id')
                ->label('Negocio sugerido')
                ->helperText('Nova mencionará este negocio como complemento.')
                ->options(fn (): array => NovaBusiness::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->required(),

            TextInput::make('cta_label')
                ->label('Texto del botón CTA')
                ->placeholder('Ver más información')
                ->maxLength(128),

            Textarea::make('message')
                ->label('Mensaje de sugerencia')
                ->helperText('Texto que verá el usuario al recibir la sugerencia.')
                ->placeholder('¿Sabías que también puedes reservar un taxi para llegar a la bodega?')
                ->rows(3)
                ->columnSpanFull(),

            TextInput::make('cta_url')
                ->label('URL del CTA')
                ->url()
                ->placeholder('https://')
                ->maxLength(512),
        ];
    }

    /** @return array<int, mixed> */
    protected static function conditionFields(): array
    {
        return [
            TagsInput::make('excluded_intents')
                ->label('Intents excluidos')
                ->helperText('Si Nova ya detectó alguno de estos intents, no aplica la regla.')
                ->columnSpanFull(),
        ];
    }
}
