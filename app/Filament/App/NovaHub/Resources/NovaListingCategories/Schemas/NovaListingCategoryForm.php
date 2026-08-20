<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaListingCategories\Schemas;

use Filament\Support\Icons\Heroicon;

use App\Models\NovaBusiness;
use App\Models\Server;
use App\Models\Tool;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class NovaListingCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Listing Category Setup')
                    ->persistTab()
                    ->tabs([
                        Tab::make('Básico')
                            ->icon(Heroicon::OutlinedTag)
                            ->schema([
                                Section::make('Categoría')
                                    ->description('Tipo de listado y negocio al que pertenece esta configuración.')
                                    ->schema(static::identityFields())
                                    ->columns(2),
                            ]),

                        Tab::make('Tool MCP')
                            ->icon(Heroicon::OutlinedWrenchScrewdriver)
                            ->schema([
                                Section::make('Conexión al servidor MCP')
                                    ->description('Servidor y herramienta que Nova ejecuta para obtener este tipo de listado.')
                                    ->schema(static::toolFields())
                                    ->columns(2),

                                Section::make('Parámetros de la tool')
                                    ->description('Parámetros fijos que se pasan siempre al llamar a la tool (clave-valor).')
                                    ->schema(static::toolParamsFields())
                                    ->columns(1)
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        Tab::make('Textos')
                            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                            ->schema([
                                Section::make('Presentación al usuario')
                                    ->description('Textos que Nova usa al presentar resultados de este tipo de listado en la conversación.')
                                    ->schema(static::textFields())
                                    ->columns(2),

                                Section::make('Keywords de detección')
                                    ->description('Palabras clave que activan esta categoría en la conversación.')
                                    ->schema(static::keywordFields())
                                    ->columns(1),
                            ]),

                        Tab::make('Avanzado')
                            ->icon(Heroicon::OutlinedCog6Tooth)
                            ->schema([
                                Section::make('Campos de ítem')
                                    ->description('Campos que Nova extrae de cada ítem devuelto por la tool.')
                                    ->schema(static::itemFieldsSection())
                                    ->columns(1)
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /** @return array<int, mixed> */
    protected static function identityFields(): array
    {
        return [
            Select::make('slug')
                ->label('Tipo de categoría')
                ->helperText('Identificador interno de este tipo de listado.')
                ->options([
                    'restaurant' => 'Restaurante',
                    'visit' => 'Visita / Experiencia',
                    'tour' => 'Tour',
                    'transfer' => 'Taxi Transfer',
                    'hotel' => 'Hotel',
                    'product' => 'Producto',
                    'taxi' => 'Taxi',
                    'villa' => 'Villa',
                    'route' => 'Ruta',
                ])
                ->native(false)
                ->required(),

            Toggle::make('is_active')
                ->label('Activo')
                ->helperText('Desactiva para ocultar sin eliminar.')
                ->default(true)
                ->inline(false),

            Select::make('nova_business_id')
                ->label('Negocio')
                ->helperText('Deja vacío para aplicar globalmente a todos los negocios.')
                ->options(fn (): array => NovaBusiness::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->nullable(),

            TextInput::make('sort_order')
                ->label('Orden')
                ->helperText('Prioridad dentro de su negocio. Menor = primero.')
                ->numeric()
                ->default(0),
        ];
    }

    /** @return array<int, mixed> */
    protected static function toolFields(): array
    {
        return [


            Select::make('server_id')
                ->label('ID del servidor MCP')
                ->helperText('ID del servidor en la tabla servers.')
                ->options(fn (): array => Server::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->nullable(),

            Select::make('tool_id')
                ->label('Nombre del tool')
                ->helperText('Nombre exacto del tool tal como lo expone el servidor MCP.')
                ->placeholder('search_restaurants, list_visits...')
                ->options(fn (Get $get): array => Tool::query()->where('server_id',$get('server_id'))->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->nullable(),

        ];
    }

    /** @return array<int, mixed> */
    protected static function toolParamsFields(): array
    {
        return [
            KeyValue::make('tool_params')
                ->label('Parámetros fijos')
                ->helperText('Se fusionan con los parámetros dinámicos de la conversación en cada llamada.')
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, mixed> */
    protected static function textFields(): array
    {
        return [
            TextInput::make('intro_text')
                ->label('Texto de introducción')
                ->helperText('Frase que Nova dice antes de mostrar los resultados.')
                ->placeholder('Aquí tienes los mejores restaurantes...')
                ->maxLength(500)
                ->columnSpanFull(),

            TextInput::make('cta_text')
                ->label('Texto CTA')
                ->helperText('Llamada a la acción al final del listado.')
                ->placeholder('¿Te gustaría reservar alguno?')
                ->maxLength(255),

            TextInput::make('count_label')
                ->label('Etiqueta de cantidad')
                ->helperText('Ej: restaurantes, visitas, habitaciones')
                ->placeholder('resultados')
                ->maxLength(100),
        ];
    }

    /** @return array<int, mixed> */
    protected static function keywordFields(): array
    {
        return [
            TagsInput::make('keywords')
                ->label('Keywords')
                ->helperText('Escribe una keyword y pulsa Enter. Nova las usará para detectar la intención.')
                ->placeholder('restaurante, comer, cenar...')
                ->columnSpanFull(),

            TagsInput::make('system_names')
                ->label('Nombres de sistema')
                ->helperText('Nombres alternativos o sinónimos internos de esta categoría.')
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, mixed> */
    protected static function itemFieldsSection(): array
    {
        return [
            KeyValue::make('item_fields')
                ->label('Mapeo de campos de ítem')
                ->helperText('Clave = campo interno de Nova, Valor = campo devuelto por el tool MCP.')
                ->columnSpanFull(),
        ];
    }
}
