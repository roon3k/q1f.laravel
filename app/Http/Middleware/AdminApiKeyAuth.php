<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiToken;
use Illuminate\Http\Request;

class AdminApiKeyAuth
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey) {
            return response()->json(['message' => 'API key is required'], 401);
        }

        $token = ApiToken::where('token', $apiKey)->first();
        
        if (!$token) {
            return response()->json(['message' => 'Invalid API key'], 401);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return response()->json(['message' => 'API key has expired'], 401);
        }

        if (!$token->user->is_admin) {
            return response()->json(['message' => 'Unauthorized. Admin access required'], 403);
        }

        $token->update(['last_used_at' => now()]);
        $request->setUserResolver(function () use ($token) {
            return $token->user;
        });

        return $next($request);
    }
} 