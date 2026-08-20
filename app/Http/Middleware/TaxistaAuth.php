<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxistaAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('taxista')->check()) {
            return redirect()->route('taxista.login');
        }

        // Verificar que el taxista esté activo
        $taxista = Auth::guard('taxista')->user();
        
        if ($taxista->status !== 'active') {
            Auth::guard('taxista')->logout();
            return redirect()->route('taxista.login')
                ->with('error', 'Su cuenta no está activa. Contacte con el administrador.');
        }

        return $next($request);
    }
}
