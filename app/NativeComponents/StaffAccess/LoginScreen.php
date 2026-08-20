<?php

declare(strict_types=1);

namespace App\NativeComponents\StaffAccess;

use App\Services\Staff\StaffApiClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;
use Throwable;

class LoginScreen extends NativeComponent
{
    public string $email = '';

    public string $password = '';

    public ?string $error = null;

    public bool $loading = false;

    public function mount(): void
    {
        if (app(StaffApiClient::class)->getToken() !== null) {
            $this->replace('/staff/dashboard');
        }
    }

    public function login(): void
    {
        $api = app(StaffApiClient::class);
        $this->error = null;

        $validator = Validator::make([
            'email' => $this->email,
            'password' => $this->password,
        ], [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            $this->error = 'Introduce email y contraseña válidos.';

            return;
        }

        $this->loading = true;

        try {
            $api->login([
                'email' => $this->email,
                'password' => $this->password,
                'device_name' => 'NOVA Staff Mobile',
            ]);

            $this->replace('/staff/dashboard');
        } catch (Throwable $exception) {
            report($exception);
            $this->error = 'Credenciales incorrectas o error de conexión.';
        } finally {
            $this->loading = false;
        }
    }

    public function render(): View
    {
        return view('native.staff-access.login-screen');
    }
}
