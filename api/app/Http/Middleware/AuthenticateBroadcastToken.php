<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBroadcastToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->bearerToken() && $request->cookie('access_token')) {
            $request->headers->set('Authorization', 'Bearer ' . $request->cookie('access_token'));
        }

        foreach (['admin', 'customer'] as $guard) {
            $user = Auth::guard($guard)->user();
            if ($user) {
                Auth::shouldUse($guard);
                $request->setUserResolver(fn() => $user);
                $request->attributes->set('broadcast_guard', $guard);

                return $next($request);
            }
        }

        throw new AuthenticationException('Unauthenticated.');
    }
}
