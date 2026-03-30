<?php

namespace App\Http\Middleware;

use App\Support\CodexRuntimeConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('register')) {
            return $next($request);
        }

        abort_unless(CodexRuntimeConfig::registrationEnabled(), 404);

        return $next($request);
    }
}
