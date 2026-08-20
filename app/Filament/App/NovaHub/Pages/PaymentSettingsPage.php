<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Pages;

use Filament\Support\Icons\Heroicon;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Models\PaymentSettings;

/**
 * Filament Settings Page for Przelewy24 configuration.
 *
 * Allows admin to configure payment gateway credentials without .env deployment.
 * Credentials are encrypted in database.
 *
 * KML-0066
 */
class PaymentSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected string $view = 'filament.pages.payment-settings';

    protected static ?string $navigationLabel = 'Pagos';

    protected static ?string $title = 'Ajustes Pagos';

    protected static string|\UnitEnum|null $navigationGroup = 'Facturación';
    protected static ?string $navigationParentGroup = 'Reservas';
    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = $this->getSettings();

        $this->form->fill([
            'merchant_id' => $settings->merchant_id,
            'pos_id' => $settings->pos_id,
            'crc' => $settings->crc,
            'api_key' => $settings->api_key,
            'sandbox' => $settings->sandbox,
            'enabled' => $settings->enabled,
            'webhook_path' => $settings->webhook_path,
            'return_path' => $settings->return_path,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $settings = $this->getSettings();

        return $schema
            ->components([
                Section::make('Przelewy24')
                    ->description('Konfiguracja bramki płatności Przelewy24')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Płatności włączone')
                            ->helperText('Wyłącz, aby tymczasowo dezaktywować płatności online')
                            ->live(),

                        Toggle::make('sandbox')
                            ->label('Tryb testowy (sandbox)')
                            ->helperText('Włącz dla testów, wyłącz dla produkcji')
                            ->live(),

                        TextInput::make('merchant_id')
                            ->label('Merchant ID')
                            ->required()
                            ->numeric()
                            ->helperText('Identyfikator sprzedawcy z panelu Przelewy24'),

                        TextInput::make('pos_id')
                            ->label('POS ID')
                            ->numeric()
                            ->helperText('Opcjonalnie - domyślnie równy Merchant ID'),

                        TextInput::make('crc')
                            ->label('Klucz CRC')
                            ->required()
                            ->password()
                            ->revealable()
                            ->helperText('Klucz CRC z panelu Przelewy24'),

                        TextInput::make('api_key')
                            ->label('Klucz API')
                            ->required()
                            ->password()
                            ->revealable()
                            ->helperText('Klucz API z panelu Przelewy24'),
                    ])
                    ->columns(2),

                Section::make('URL-e do konfiguracji w panelu P24')
                    ->description('Skopiuj te adresy do panelu Przelewy24')
                    ->schema([
                        TextEntry::make('webhook_url_display')
                            ->label('URL powiadomień (webhook)')
                            ->state(fn () => $settings->getWebhookUrl())
                            ->helperText('Wklej w panelu P24 jako adres powiadomień o statusie transakcji'),

                        TextEntry::make('return_url_display')
                            ->label('URL powrotu')
                            ->state(fn () => $settings->getReturnUrl())
                            ->helperText('Adres powrotu po płatności (automatycznie używany)'),

                        TextInput::make('webhook_path')
                            ->label('Ścieżka webhook')
                            ->default('/api/payment/webhook')
                            ->helperText('Zmień tylko jeśli wiesz co robisz'),

                        TextInput::make('return_path')
                            ->label('Ścieżka powrotu')
                            ->default('/rezerwacja/status')
                            ->helperText('Ścieżka strony po zakończeniu płatności'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('Status konfiguracji')
                    ->schema([
                        TextEntry::make('config_status')
                            ->label('Status')
                            ->state(function () use ($settings) {
                                if (! $settings->isConfigured()) {
                                    return '❌ Brak wymaganych danych (merchant_id, crc, api_key)';
                                }
                                if (! $settings->enabled) {
                                    return '⚠️ Skonfigurowane, ale wyłączone';
                                }
                                if ($settings->sandbox) {
                                    return '🧪 Aktywne (tryb testowy)';
                                }

                                return '✅ Aktywne (produkcja)';
                            }),
                    ]),

                // KML-0049 (UX follow-up): Save action zgodny z Filament native.
                Actions::make([
                    Action::make('save')
                        ->label('Zapisz konfigurację')
                        ->submit('save')
                        ->color('primary')
                        ->keyBindings(['mod+s']),
                ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = $this->getSettings();
        $settings->update([
            'merchant_id' => $data['merchant_id'] ?? null,
            'pos_id' => $data['pos_id'] ?? null,
            'crc' => $data['crc'] ?? null,
            'api_key' => $data['api_key'] ?? null,
            'sandbox' => $data['sandbox'] ?? true,
            'enabled' => $data['enabled'] ?? false,
            'webhook_path' => $data['webhook_path'] ?? '/api/payment/webhook',
            'return_path' => $data['return_path'] ?? '/rezerwacja/status',
        ]);

        Notification::make()
            ->title('Zapisano')
            ->body('Konfiguracja płatności została zaktualizowana.')
            ->success()
            ->send();
    }

    protected function getSettings(): PaymentSettings
    {
        // TODO: W multi-tenant pobierz tenant_id z kontekstu (np. Filament::getTenant())
        $tenantId = null;

        return PaymentSettings::getOrCreate($tenantId, 'przelewy24');
    }

}
