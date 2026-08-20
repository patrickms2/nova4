<?php

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NovaBusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cliente / negocio')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique()
                            ->maxLength(255),
                        Select::make('business_type')
                            ->label('Tipo')
                            ->required()
                            ->options([
                                'taxi' => 'Taxi / traslados',
                                'hotel' => 'Hotel / apartamentos',
                                'restaurant' => 'Restaurante',
                                'activity' => 'Actividad / visitas',
                                'commerce' => 'Comercio',
                                'winery' => 'Bodega',
                                'magento' => 'Magento',
                                'woocommerce' => 'WooCommerce',
                                'other' => 'Otro',
                            ])
                            ->default('other'),
                        Select::make('status')
                            ->label('Estado')
                            ->required()
                            ->options([
                                'active' => 'Activo',
                                'trial' => 'Prueba',
                                'paused' => 'Pausado',
                                'draft' => 'Borrador',
                            ])
                            ->default('active'),
                    ])
                    ->columns(2),
                Section::make('Contacto')
                    ->schema([
                        TextInput::make('contact_name')
                            ->label('Contacto')
                            ->maxLength(255),
                        TextInput::make('contact_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('contact_phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('website_url')
                            ->label('Web')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Modelo económico')
                    ->schema([
                        TextInput::make('subscription_amount')
                            ->label('Suscripción mensual')
                            ->numeric()
                            ->prefix('€')
                            ->default(200),
                        TextInput::make('commission_rate')
                            ->label('Comisión')
                            ->numeric()
                            ->suffix('%')
                            ->default(10),
                        TagsInput::make('settings.recognition_terms')
                            ->label('Términos de reconocimiento')
                            ->helperText('Palabras clave que identifican este negocio en los mensajes del bot.')
                            ->placeholder('Ej: geria, bodega, vino')
                            ->columnSpanFull(),

                        KeyValue::make('settings')
                            ->label('Configuración avanzada')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
