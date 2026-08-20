<?php

namespace App\Filament\App\Pages\Auth;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Schema as DbSchema;

class Login extends \Filament\Auth\Pages\Login
{
    public ?string $heading = 'Portal Taxistas';

    protected string $view = 'filament.app.auth.custom-login-view';

    public function getHeading(): string
    {
        return 'Portal Taxistas';
    }

    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Acceder al Portal')
            ->icon('heroicon-o-truck')
            ->extraAttributes([
                'x-data' => '{ showQuickLogin: true }',
                'x-init' => '$wire.quickLogin = function() { $wire.form.fill({ email: "admin@taxilanz.com", password: "password" }); }',
            ])
            ->submit('authenticate');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('NIF o Email')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraAttributes([
                'class' => 'text-white',
                'id' => 'username',
                'autofocus' => true,
            ])->maxLength(255)
            ->email(false);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $identifier = trim((string)($data['email'] ?? ''));

        $credentials = [
            'password' => $data['password'],
        ];

        if (DbSchema::hasColumn('users', 'status')) {
            $credentials['status'] = true;
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $credentials['email'] = strtolower($identifier);

            return $credentials;
        }

        $credentials['nif'] = strtoupper($identifier);

        return $credentials;
    }

    public function quickLogin(?string $role = null): void
    {
        $credentials = match ($role) {
            'laboral' => [
                'email' => 'laboral@taxilanz.com',
                'password' => 'password',
            ],
            'fiscal' => [
                'email' => 'fiscal@taxilanz.com',
                'password' => 'password',
            ],
            'rrhh' => [
                'email' => 'rrhh@taxilanz.com',
                'password' => 'password',
            ],
            'soporte' => [
                'email' => 'soporte@taxilanz.com',
                'password' => 'password',
            ],
            'admin' => [
                'email' => 'super@taxilanz.com',
                'password' => 'password',
            ],
            default => [
                'email' => 'super@taxilanz.com',
                'password' => 'password',
            ],
        };

        $this->form->fill($credentials);
    }

    public function hasCustomLoginView(): bool
    {
        return true;
    }
}
