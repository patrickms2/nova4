<?php

namespace App\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class PortalLogoutController
{
    public function __invoke(Request $request): RedirectResponse
    {
        Filament::auth()->logout();
        auth('taxista')->logout();
        auth('web')->logout();
        auth('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->guest(route('filament.portal.auth.login'));
    }
}
