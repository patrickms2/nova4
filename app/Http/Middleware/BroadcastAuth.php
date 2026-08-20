<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BroadcastAuth
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $preferredGuards = $this->resolvePreferredGuards($request);
        $configuredGuards = array_keys((array) config('auth.guards', []));

        $guards = array_values(array_unique(array_merge($preferredGuards, $configuredGuards)));

        foreach ($guards as $guard) {
            if (! is_string($guard) || $guard === '') {
                continue;
            }

            if (! Auth::guard($guard)->check()) {
                continue;
            }

            Auth::shouldUse($guard);

            $request->setUserResolver(static fn () => Auth::guard($guard)->user());

            return $next($request);
        }

        abort(403);
    }

    /**
     * @return array<int, string>
     */
    protected function resolvePreferredGuards(Request $request): array
    {
        $channelName = (string) $request->input('channel_name', '');
        $refererPath = (string) parse_url((string) $request->headers->get('referer', ''), PHP_URL_PATH);

        if (Str::startsWith($refererPath, '/portal')) {
            return ['taxista', 'web', 'admin', 'partner'];
        }

        if (Str::startsWith($refererPath, '/app')) {
            return ['web', 'admin', 'taxista', 'partner'];
        }

        if (Str::contains($channelName, 'App.Models.Taxista.')) {
            return ['taxista', 'web', 'admin', 'partner'];
        }

        if (Str::contains($channelName, 'App.Models.User.') || Str::contains($channelName, 'support.user.')) {
            return ['web', 'admin', 'taxista', 'partner'];
        }

        return ['web', 'admin', 'taxista', 'partner'];
    }
}
