<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class ApiAuthenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            return route('login');
        }
        return null;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $apiToken = \App\Models\ApiToken::where('token', $token)->first();
        
        if (!$apiToken) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        if ($apiToken->expires_at && $apiToken->expires_at->isPast()) {
            return response()->json(['message' => 'Token has expired.'], 401);
        }

        $apiToken->update(['last_used_at' => now()]);
        $request->setUserResolver(function () use ($apiToken) {
            return $apiToken->user;
        });

        return $next($request);
    }
} 