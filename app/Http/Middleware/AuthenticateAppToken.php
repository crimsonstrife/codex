<?php

namespace App\Http\Middleware;

use App\Models\AppToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate requests using an app-level token (not user-scoped).
 * Used for server-to-server integrations (e.g. Forge calling Codex API).
 *
 * Expects a Bearer token in the Authorization header.
 * The raw token is hashed and compared against the app_tokens table.
 */
class AuthenticateAppToken
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $raw = $request->bearerToken();

        if (! $raw) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = AppToken::findByRawToken($raw);

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Optionally check that the token has specific required abilities.
        foreach ($abilities as $ability) {
            if (! $token->can($ability)) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        $token->touchLastUsed();

        // Make the token available to controllers via $request->attributes.
        $request->attributes->set('app_token', $token);

        return $next($request);
    }
}
