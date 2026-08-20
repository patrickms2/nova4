<?php

namespace App\Filament\Resources\NovaWhatsappChannels\Schemas;

use App\Models\NovaBusiness;
use App\Models\NovaService;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema as Form;
use Filament\Schemas\Schema;

class NovaWhatsappChannelForm
{
    public static function configure(Schema $schema): Form
    {
        return $schema
            ->schema([
                Section::make('Canal WhatsApp')
                    ->schema([
                        Select::make('nova_business_id')
                            ->label('Cliente')
                            ->options(fn(): array => NovaBusiness::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('nova_service_id')
                            ->label('Servicio')
                            ->options(fn(): array => NovaService::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->preload(),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Select::make('provider')
                            ->label('Proveedor')
                            ->options([
                                'meta' => 'Meta WhatsApp Cloud',
                                'twilio' => 'Twilio',
                                '360dialog' => '360dialog',
                                'other' => 'Otro',
                            ])
                            ->default('meta'),
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'draft' => 'Borrador',
                                'active' => 'Activo',
                                'paused' => 'Pausado',
                                'error' => 'Error',
                            ])->default('draft'),
                        TextInput::make('phone_number')
                            ->label('Número WA')
                            ->tel(),
                        TextInput::make('phone_number_id')
                            ->label('Phone Number ID'),
                        TextInput::make('business_account_id')
                            ->label('Business Account ID'),
                        TextInput::make('webhook_url')
                            ->label('Webhook URL')
                            ->url(),
                        KeyValue::make('credentials')
                            ->label('Credenciales')
                            ->helperText('Se guardan cifradas.')
                            ->columnSpanFull(),
                        KeyValue::make('settings')
                            ->label('Configuración'),
                    ])
                    ->columns(2),
            ]);
    }
}
