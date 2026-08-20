<?php

namespace App\Filament\Portal\Pages\Auth;

use App\Models\Taxista;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login2 extends BaseLogin
{
    public ?string $heading = 'Portal Taxistas';

    protected string $view = 'filament.portal.auth.custom-login-view';

    public function getHeading(): string
    {
        return 'Portal Taxistas';
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $credentials = $this->getCredentialsFromFormData($data);
        $remember = (bool) ($data['remember'] ?? false);

        if ($this->attemptEmployeeLogin($credentials, $remember)) {
            session()->regenerate();

            return app(LoginResponse::class);
        }

        if (Filament::auth()->attempt($credentials, $remember)) {
            session()->regenerate();

            return app(LoginResponse::class);
        }

        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('NIF o Email')
            ->required()
            ->autocomplete('username')
            ->extraAttributes([
                'id' => 'form_email',
                'class' => 'email',
            ])
            ->autofocus()
            ->maxLength(255)
            ->email(false);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->required()
            ->autocomplete('current-password')
            ->extraAttributes([
                'id' => 'form_password',
                'class' => 'password',
            ]);
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Acceder al Portal')
            ->icon('heroicon-o-truck');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $identifier = trim((string) ($data['email'] ?? ''));

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

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function attemptEmployeeLogin(array $credentials, bool $remember): bool
    {
        if (! Auth::guard('web')->attempt($credentials, $remember)) {
            return false;
        }

        $user = Auth::guard('web')->user();

        if (! $user) {
            return false;
        }

        $role = strtolower(trim((string) ($user->role ?? '')));
        $isEmployee = (bool) ($user->is_employee ?? false) || $role === 'empleado';
        $isActive = ! DbSchema::hasColumn('users', 'status')
            || (bool) ($user->getAttributes()['status'] ?? false);

        if (! $isEmployee || ! $isActive) {
            Auth::guard('web')->logout();

            return false;
        }

        $userId = (int) ($user->getAuthIdentifier() ?? 0);

        Auth::guard('web')->logout();

        if ($userId <= 0) {
            return false;
        }

        $taxista = Taxista::query()->find($userId);

        if (! $taxista) {
            return false;
        }

        Auth::guard('taxista')->login($taxista, $remember);

        return Auth::guard('taxista')->check();
    }
}
