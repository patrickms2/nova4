<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class FilamentLogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        Filament::auth()->logout();
        auth('taxista')->logout();
        auth('web')->logout();
        auth('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->guest(
            Filament::hasLogin()
                ? Filament::getLoginUrl()
                : route('login'),
        );
    }
}
