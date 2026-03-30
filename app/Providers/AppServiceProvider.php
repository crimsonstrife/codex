<?php

namespace App\Providers;

use App\Listeners\AcceptPendingWorkspaceInvitations;
use App\Socialite\ForgeProvider;
use App\Support\CodexRuntimeConfig;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        if (CodexRuntimeConfig::forgeSsoAvailable()) {
            Socialite::extend('forge', function () {
                return Socialite::buildProvider(ForgeProvider::class, [
                    'client_id' => config('codex.forge.client_id'),
                    'client_secret' => config('codex.forge.client_secret'),
                    'redirect' => config('codex.forge.redirect_uri'),
                    'guzzle' => [
                        'verify' => ! CodexRuntimeConfig::forgeDisableTlsVerification(),
                    ],
                ]);
            });
        }

        RateLimiter::for('api', static function (Request $request) {
            $key = optional($request->user())?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(120)->by($key);
        });

        // Sprint 14.3: auto-accept workspace invitations after any login
        Event::listen(Login::class, AcceptPendingWorkspaceInvitations::class);
    }
}
