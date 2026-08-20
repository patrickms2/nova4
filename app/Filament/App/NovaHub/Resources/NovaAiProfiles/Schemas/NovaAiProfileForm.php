<?php

namespace App\Filament\App\NovaHub\Resources\NovaAiProfiles\Schemas;

use Filament\Support\Icons\Heroicon;

use App\Models\NovaBusiness;
use App\Models\NovaService;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class NovaAiProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Agent Setup')
                    ->persistTab()
                    ->tabs([
                        Tab::make('Básico')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->schema([
                                Section::make('Identidad del agente')
                                    ->description('Nombre del perfil IA y cliente al que pertenece. Visible solo en el panel admin.')
                                    ->schema(static::identityFields())
                                    ->columns(2),
                            ]),

                        Tab::make('IA & Modelo')
                            ->icon(Heroicon::OutlinedCpuChip)
                            ->schema([
                                Section::make('Proveedor y modelo')
                                    ->description('Selecciona el servicio IA y el modelo que genera las respuestas.')
                                    ->schema(static::modelFields())
                                    ->columns(2),

                                Section::make('System prompt')
                                    ->description('Instrucciones base que definen el rol, tono y restricciones del agente.')
                                    ->schema(static::promptFields())
                                    ->columns(1),

                                Section::make('Parámetros avanzados')
                                    ->description('Temperatura, tokens y ajustes de generación. Valores por defecto recomendados para la mayoría de casos.')
                                    ->schema(static::advancedModelFields())
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        Tab::make('Comportamiento')
                            ->icon(Heroicon::OutlinedCog6Tooth)
                            ->schema([
                                Section::make('Política de tools')
                                    ->description('Define qué herramientas MCP puede usar este agente y bajo qué condiciones.')
                                    ->schema(static::toolsPolicyFields())
                                    ->columns(1)
                                    ->collapsible()
                                    ->collapsed(),

                                Section::make('Configuración adicional')
                                    ->description('Parámetros adicionales de comportamiento en formato clave-valor.')
                                    ->schema(static::settingsFields())
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
            TextInput::make('name')
                ->label('Nombre')
                ->placeholder('Ej: Agente La Geria, Nova Support')
                ->helperText('Solo visible en el panel admin.')
                ->required()
                ->maxLength(255),

            Select::make('status')
                ->label('Estado')
                ->options([
                    'draft' => 'Borrador',
                    'active' => 'Activo',
                    'paused' => 'Pausado',
                ])
                ->default('draft')
                ->native(false),

            Select::make('nova_business_id')
                ->label('Negocio')
                ->options(fn (): array => NovaBusiness::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->required(),

            Select::make('nova_service_id')
                ->label('Servicio')
                ->options(fn (): array => NovaService::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->helperText('Opcional. Vincula el perfil a un servicio concreto.'),
        ];
    }

    /** @return array<int, mixed> */
    protected static function modelFields(): array
    {
        return [
            Select::make('provider')
                ->label('Proveedor')
                ->options([
                    'openai' => 'OpenAI',
                    'anthropic' => 'Anthropic',
                    'google' => 'Google',
                    'local' => 'Local',
                    'other' => 'Otro',
                ])
                ->default('openai')
                ->native(false)
                ->required(),

            TextInput::make('model')
                ->label('Modelo')
                ->placeholder('gpt-4.1-mini')
                ->helperText('Ej: gpt-4o, claude-3-5-sonnet, gemini-2.0-flash')
                ->required()
                ->default('gpt-4.1-mini')
                ->maxLength(255),
        ];
    }

    /** @return array<int, mixed> */
    protected static function promptFields(): array
    {
        return [
            Textarea::make('system_prompt')
                ->label('System prompt')
                ->helperText('Define el rol, tono, idioma y restricciones del agente.')
                ->rows(10)
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, mixed> */
    protected static function advancedModelFields(): array
    {
        return [
            TextInput::make('temperature')
                ->label('Temperatura')
                ->helperText('0 = determinista, 1 = creativo. Recomendado: 0.3')
                ->numeric()
                ->default(0.30),

            TextInput::make('max_tokens')
                ->label('Máx. tokens')
                ->helperText('Límite de tokens por respuesta. Dejar vacío para usar el máximo del modelo.')
                ->numeric(),
        ];
    }

    /** @return array<int, mixed> */
    protected static function toolsPolicyFields(): array
    {
        return [
            KeyValue::make('tools_policy')
                ->label('Política de tools')
                ->helperText('Clave = nombre del tool, Valor = allow | deny | require')
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, mixed> */
    protected static function settingsFields(): array
    {
        return [
            KeyValue::make('settings')
                ->label('Configuración adicional')
                ->columnSpanFull(),
        ];
    }
}
