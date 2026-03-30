<?php

namespace Tests\Unit;

use App\Socialite\ForgeProvider;
use Illuminate\Http\Request;
use Tests\TestCase;

class ForgeProviderTest extends TestCase
{
    public function test_it_requests_only_the_profile_scope_for_forge_sso(): void
    {
        config()->set('codex.forge.url', 'https://forge.example.com');

        $provider = (new ForgeProvider(
            Request::create('/auth/forge/redirect', 'GET'),
            'client-id',
            'client-secret',
            'https://codex.example.com/auth/forge/callback',
        ))->stateless();

        $targetUrl = $provider->redirect()->getTargetUrl();

        $this->assertStringStartsWith('https://forge.example.com/oauth/authorize?', $targetUrl);

        parse_str(parse_url($targetUrl, PHP_URL_QUERY) ?? '', $query);

        $this->assertSame('profile', $query['scope'] ?? null);
    }
}
