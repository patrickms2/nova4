<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaServices\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NovaServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            static::identityFields(),
            static::featuresFields(),
            static::billingFields(),
        ]);
    }

    public static function identityFields(): Section
    {
        return Section::make('Servicio')
            ->description('Nombre, tipo y estado del servicio contratado.')
            ->columns(3)
            ->schema([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                TextInput::make('code')
                    ->label('Código')
                    ->maxLength(255),

                Select::make('service_type')
                    ->label('Tipo')
                    ->required()
                    ->options([
                        'whatsapp_bot' => 'WhatsApp Bot',
                        'development' => 'Desarrollo',
                        'maintenance' => 'Mantenimiento',
                        'sales' => 'Venta',
                        'services' => 'Servicios',
                        'other' => 'Otro',
                    ])
                    ->default('services'),

                Select::make('status')
                    ->label('Estado')
                    ->required()
                    ->options([
                        'active' => 'Activo',
                        'paused' => 'Pausado',
                        'draft' => 'Borrador',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('active'),

                Textarea::make('notes')
                    ->label('Notas')
                    ->rows(3)
                    ->columnSpanFull(),

                KeyValue::make('settings')
                    ->label('Configuración')
                    ->columnSpanFull(),
            ]);
    }

    public static function featuresFields(): Section
    {
        return Section::make('Módulos activos')
            ->description('Capacidades incluidas en este servicio.')
            ->columns(3)
            ->schema([
                Toggle::make('has_development')->label('Desarrollo'),
                Toggle::make('has_maintenance')->label('Mantenimiento'),
                Toggle::make('has_whatsapp')->label('WhatsApp'),
                Toggle::make('has_mcp')->label('MCP'),
                Toggle::make('has_sales')->label('Venta'),
                Toggle::make('has_services')->label('Servicios')->default(true),
            ]);
    }

    public static function billingFields(): Section
    {
        return Section::make('Facturación')
            ->columns(2)
            ->schema([
                TextInput::make('monthly_amount')
                    ->label('Cuota mensual')
                    ->numeric()
                    ->prefix('€'),

                TextInput::make('commission_rate')
                    ->label('Comisión')
                    ->numeric()
                    ->suffix('%'),
            ]);
    }
}
