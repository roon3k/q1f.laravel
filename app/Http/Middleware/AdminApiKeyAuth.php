<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();
        
        if (!$bearerToken) {
            return response()->json(['message' => 'API token is required'], 401);
        }

        $token = ApiToken::where('token', $bearerToken)->first();
        
        if (!$token) {
            return response()->json(['message' => 'Invalid API token'], 401);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return response()->json(['message' => 'API token has expired'], 401);
        }

        if (!$token->user || !$token->user->is_admin) {
            return response()->json(['message' => 'Unauthorized. Admin access required'], 403);
        }

        $token->update(['last_used_at' => now()]);
        $request->setUserResolver(function () use ($token) {
            return $token->user;
        });

        return $next($request);
    }
} 