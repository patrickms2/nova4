<?php

// app/Http/Middleware/CheckAdminAccess.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRotueAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            // Redirect to dashboard or show 403
            return redirect('/login')->with('error', 'Unauthorized access');

            // Or abort with 403
            // abort(403, 'Unauthorized access to admin panel');
        }

        // Check if user has admin role
        if (! in_array($user->role, ['admin', 'super_admin', 'super', 'superadmin', 'sudo', 'root'])) {
            // Redirect to dashboard or show 403
            return redirect('/dashboard')->with('error', 'Unauthorized access');

            // Or abort with 403
            // abort(403, 'Unauthorized access to admin panel');
        }

        return $next($request);
    }
}
