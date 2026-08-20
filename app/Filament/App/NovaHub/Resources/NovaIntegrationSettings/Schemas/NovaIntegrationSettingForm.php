<?php

namespace App\Filament\App\NovaHub\Resources\NovaIntegrationSettings\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NovaIntegrationSettingForm
{
    public static function configure(Schema $schema): Schema
    {

        return $schema
            ->components([
                Section::make('Integración')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Select::make('nova_business_id')
                            ->label('Cliente Nova')
                            ->relationship('business', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('nova_service_id')
                            ->label('Servicio Nova')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activa',
                                'paused' => 'Pausada',
                                'draft' => 'Borrador',
                            ])
                            ->default('active')
                            ->required(),
                        Select::make('source_type')
                            ->label('Origen')
                            ->options([
                                'woo' => 'WooCommerce',
                                'latepoint' => 'LatePoint',
                                'woo_latepoint' => 'Woo + LatePoint',
                                'magento' => 'Magento',
                                'wordpress' => 'WordPress',
                                'laravel' => 'Laravel',
                            ])
                            ->required(),
                        Select::make('connection_type')
                            ->label('Conexión')
                            ->options([
                                'api' => 'API',
                                'database' => 'Base de datos',
                                'mcp' => 'MCP',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Endpoint')
                    ->schema([
                        TextInput::make('base_url')
                            ->label('Base URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('api_url')
                            ->label('API URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('auth_type')
                            ->label('Autenticación')
                            ->maxLength(255),
                    ])
                    ->columns(3),
                Section::make('Base de datos externa')
                    ->schema([
                        TextInput::make('external_db_driver')
                            ->label('Driver')
                            ->maxLength(255),
                        TextInput::make('external_db_host')
                            ->label('Host')
                            ->maxLength(255),
                        TextInput::make('external_db_port')
                            ->label('Puerto')
                            ->maxLength(255),
                        TextInput::make('external_db_database')
                            ->label('Base de datos')
                            ->maxLength(255),
                        TextInput::make('external_db_username')
                            ->label('Usuario')
                            ->maxLength(255),
                        TextInput::make('external_db_prefix')
                            ->label('Prefijo')
                            ->maxLength(255),
                    ])
                    ->columns(3),
                Section::make('Ajustes')
                    ->schema([
                        KeyValue::make('settings')
                            ->label('Settings')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
