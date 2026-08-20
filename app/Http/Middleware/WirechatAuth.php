<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WirechatAuth
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('taxista')->check()) {
            $taxistaId = Auth::guard('taxista')->id();

            $user = $taxistaId ? User::query()->find($taxistaId) : null;

            Auth::shouldUse('web');

            if ($user) {
                Auth::guard('web')->login($user);
            }

            $request->setUserResolver(static fn () => $user);

            return $next($request);
        }

        if (Auth::guard('web')->check()) {
            Auth::shouldUse('web');

            $request->setUserResolver(static fn () => Auth::guard('web')->user());

            return $next($request);
        }

        return redirect()->guest(route('filament.portal.auth.login'));
    }
}
