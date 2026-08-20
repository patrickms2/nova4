<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaIntentRules\Schemas;

use Filament\Support\Icons\Heroicon;

use App\Models\NovaBusiness;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class NovaIntentRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Intent Rule Setup')
                    ->persistTab()
                    ->tabs([
                        Tab::make('Básico')
                            ->icon(Heroicon::OutlinedBolt)
                            ->schema([
                                Section::make('Regla de intent')
                                    ->description('Qué intent detecta esta regla y cómo actúa sobre la conversación.')
                                    ->schema(static::ruleFields())
                                    ->columns(2),
                            ]),

                        Tab::make('Keywords')
                            ->icon(Heroicon::OutlinedMagnifyingGlass)
                            ->schema([
                                Section::make('Palabras clave')
                                    ->description('Keywords que Nova analiza en el mensaje del usuario para detectar este intent.')
                                    ->schema(static::keywordFields())
                                    ->columns(1),
                            ]),

                        Tab::make('Alcance')
                            ->icon(Heroicon::OutlinedBuildingStorefront)
                            ->schema([
                                Section::make('Negocio y prioridad')
                                    ->description('Ámbito de aplicación de la regla. Las reglas por negocio tienen prioridad sobre las globales.')
                                    ->schema(static::scopeFields())
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /** @return array<int, mixed> */
    protected static function ruleFields(): array
    {
        return [

Select::make('nova_business_id')
                                ->relationship('business', 'name')
                                ->searchable()
                                ->required()
                                ->preload()
                                ->columnSpan(1)
                                ->createOptionForm([
                                    TextInput::make('nombretotal')
                                        ->label('Nombre')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('email')
                                        ->label('Email')
                                        ->required()
                                        ->email()
                                        ->maxLength(255)
                                        ->unique(),
                                    TextInput::make('telefono')
                                        ->label('Teléfono')
                                        ->maxLength(255),
                                ])
                                ->createOptionAction(fn (Action $action) => $action
                                    ->modalHeading('Crear cliente')
                                    ->modalSubmitActionLabel('Nuevo Cliente')
                                    ->modalWidth('lg')),

            Select::make('intent_key')
                ->label('Intent')
                ->helperText('Intent que identifica esta regla en el sistema.')
                ->options([
                    'comercial_info' => 'Información comercial',
                    'system_info' => 'Información del sistema',
                    'catalog_info' => 'Información del catálogo',
                    'booking_info' => 'Información de reservas',
                    'services_info' => 'Información de servicios',
                    'taxi' => 'Taxi / Traslado',
                    'restaurant' => 'Restaurante',
                    'visit' => 'Visita / Experiencia',
                    'hotel' => 'Hotel',
                    'tour' => 'Tour / Actividad',
                    'activity' => 'Actividad',
                    'winery_visit' => 'Visita a bodega',
                    'product' => 'Producto',
                    'route' => 'Ruta',
                    'booking' => 'Reserva genérica',
                    'price' => 'Precio / Tarifa',
                    'availability' => 'Disponibilidad',
                    'recommendation' => 'Recomendación',
                    'info' => 'Información general',
                    'complaint' => 'Queja / Incidencia',
                    'greeting' => 'Saludo',
                    'farewell' => 'Despedida',
                    'generic' => 'Genérico',
                ])
                ->native(false)
                ->searchable()
                ->required(),

            ToggleButtons::make('rule_type')
                ->label('Tipo de regla')
                ->helperText('Include = al detectar estas keywords, asigna el intent. Exclude = las ignora. Boost = aumenta la confianza.')
                ->options([
                    'include' => 'Include',
                    'exclude' => 'Exclude',
                    'boost' => 'Boost',
                ])
                ->colors([
                    'include' => 'success',
                    'exclude' => 'danger',
                    'boost' => 'warning',
                ])
                ->inline()
                ->default('include')
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

            Textarea::make('description')
                ->label('Descripción')
                ->helperText('Notas internas sobre el propósito de esta regla.')
                ->rows(2)
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, mixed> */
    protected static function keywordFields(): array
    {
        return [
            TagsInput::make('keywords')
                ->label('Keywords')
                ->helperText('Escribe una keyword y pulsa Enter. Nova las analiza en los mensajes del usuario.')
                ->placeholder('restaurante, cenar, mesa...')
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, mixed> */
    protected static function scopeFields(): array
    {
        return [
            Select::make('nova_business_id')
                ->label('Negocio')
                ->helperText('Deja vacío para aplicar la regla globalmente a todos los negocios.')
                ->options(fn (): array => NovaBusiness::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->nullable(),

            TextInput::make('priority')
                ->label('Prioridad')
                ->helperText('Menor número = mayor prioridad en caso de conflicto entre reglas.')
                ->numeric()
                ->default(0),
        ];
    }
}
