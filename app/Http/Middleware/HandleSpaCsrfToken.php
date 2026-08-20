<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HandleSpaCsrfToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // For AJAX requests from SPA, ensure session is maintained
        if ($request->ajax() && ! $request->isMethod('HEAD') && $request->hasHeader('X-CSRF-TOKEN')) {
            // Regenerate CSRF token if session is old
            if (Session::has('last_activity') &&
                now()->diffInMinutes(Session::get('last_activity')) > 30) {
                Session::regenerateToken();
                Session::put('last_activity', now());
            }
        }

        // Add CSRF token to response headers for AJAX requests
        $response = $next($request);
        
        if ($request->ajax() && $response instanceof \Illuminate\Http\Response) {
            $response->headers->set('X-CSRF-TOKEN', csrf_token());
        }

        // Update last activity time
        Session::put('last_activity', now());

        return $response;
    }
}
