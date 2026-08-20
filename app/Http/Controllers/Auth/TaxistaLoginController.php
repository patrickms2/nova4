<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Taxista;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TaxistaLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.taxista-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nif' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ]);

        // Buscar taxista por NIF
        $taxista = Taxista::where('nif', $request->nif)->first();

        if (! $taxista || ! Hash::check($request->password, $taxista->password)) {
            throw ValidationException::withMessages([
                'nif' => __('Las credenciales proporcionadas no son correctas.'),
            ]);
        }

        // Verificar que el taxista esté activo
        if (! $taxista->status) {
            throw ValidationException::withMessages([
                'nif' => __('Su cuenta de taxista no está activa. Contacte con el administrador.'),
            ]);
        }

        // Autenticar al taxista
        Auth::guard('taxista')->login($taxista, $request->boolean('remember'));

        $request->session()->regenerate();

        // Redirigir al portal taxista
        return redirect(route('taxista.portal'));
    }

    public function logout(Request $request)
    {
        Auth::guard('taxista')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function dashboard()
    {
        if (! Auth::guard('taxista')->check()) {
            return redirect('/taxista/login');
        }

        return view('livewire.portal-taxista-pro');
    }
}
