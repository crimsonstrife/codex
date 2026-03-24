<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ConnectedApp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class ForgeSsoController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! config('codex.forge.enabled')) {
            return redirect()->route('login')->with('error', 'Forge SSO is not enabled.');
        }

        return Socialite::driver('forge')->redirect();
    }

    public function callback(): RedirectResponse
    {
        if (! config('codex.forge.enabled')) {
            return redirect()->route('login')->with('error', 'Forge SSO is not enabled.');
        }

        try {
            $socialUser = Socialite::driver('forge')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Failed to authenticate with Forge.');
        }

        // 1. Try matching by forge_user_id (fast path after first login)
        $user = User::where('forge_user_id', $socialUser->getId())->first();

        // 2. Fall back to email match (handles pre-existing local accounts)
        if (! $user) {
            $email = $socialUser->getEmail();

            if (! $email) {
                return redirect()->route('login')
                    ->with('error', 'Forge did not return an email address. Please ensure your Forge account has a verified email.');
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'              => $socialUser->getName() ?? $socialUser->getNickname() ?? $email,
                    'password'          => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                ]
            );
        }

        // 3. Stamp the forge_user_id for fast future lookups
        if (! $user->forge_user_id) {
            $user->forge_user_id = $socialUser->getId();
            $user->save();
        }

        // 4. Store/refresh the OAuth tokens for possible future per-user API calls
        ConnectedApp::updateOrCreate(
            [
                'user_id'  => $user->id,
                'provider' => 'forge',
            ],
            [
                'provider_user_id' => (string) $socialUser->getId(),
                'access_token'     => $socialUser->token,
                'refresh_token'    => $socialUser->refreshToken,
                'token_expires_at' => $socialUser->expiresIn
                    ? now()->addSeconds((int) $socialUser->expiresIn)
                    : null,
                'scopes'           => $socialUser->approvedScopes ?? [],
            ]
        );

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}
