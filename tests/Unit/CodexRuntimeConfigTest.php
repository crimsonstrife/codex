<?php

namespace Tests\Unit;

use App\Settings\AuthSettings;
use App\Settings\CodexSettings;
use App\Settings\ForgeSettings;
use App\Support\CodexRuntimeConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CodexRuntimeConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prefers_persisted_settings_values(): void
    {
        config()->set('codex.forge.client_id', 'forge-client');
        config()->set('codex.forge.client_secret', 'forge-secret');

        $authSettings = app(AuthSettings::class);
        $authSettings->allowRegistration = false;
        $authSettings->save();

        $codexSettings = app(CodexSettings::class);
        $codexSettings->defaultPageContentType = 'richtext';
        $codexSettings->drawioUrl = 'https://drawio.example.com';
        $codexSettings->save();

        $forgeSettings = app(ForgeSettings::class);
        $forgeSettings->enabled = true;
        $forgeSettings->url = 'https://forge.example.com';
        $forgeSettings->disableTlsVerification = true;
        $forgeSettings->save();

        $this->assertFalse(CodexRuntimeConfig::registrationEnabled());
        $this->assertSame('richtext', CodexRuntimeConfig::defaultPageContentType());
        $this->assertSame('https://drawio.example.com', CodexRuntimeConfig::drawioUrl());
        $this->assertSame('https://forge.example.com', CodexRuntimeConfig::forgeUrl());
        $this->assertTrue(CodexRuntimeConfig::forgeDisableTlsVerification());
        $this->assertTrue(CodexRuntimeConfig::forgeSsoAvailable());
    }

    public function test_it_falls_back_to_config_when_settings_cannot_be_loaded(): void
    {
        config()->set('codex.editor.default_content_type', 'richtext');
        config()->set('codex.diagrams.drawio_url', 'https://drawio.fallback.test');
        config()->set('codex.forge.enabled', true);
        config()->set('codex.forge.url', 'https://forge.fallback.test');
        config()->set('codex.forge.disable_tls_verification', true);
        config()->set('codex.forge.client_id', 'forge-client');
        config()->set('codex.forge.client_secret', 'forge-secret');

        Schema::drop('settings');

        app()->forgetInstance(AuthSettings::class);
        app()->forgetInstance(CodexSettings::class);
        app()->forgetInstance(ForgeSettings::class);

        $this->assertTrue(CodexRuntimeConfig::registrationEnabled());
        $this->assertSame('richtext', CodexRuntimeConfig::defaultPageContentType());
        $this->assertSame('https://drawio.fallback.test', CodexRuntimeConfig::drawioUrl());
        $this->assertSame('https://forge.fallback.test', CodexRuntimeConfig::forgeUrl());
        $this->assertTrue(CodexRuntimeConfig::forgeDisableTlsVerification());
        $this->assertTrue(CodexRuntimeConfig::forgeSsoAvailable());
    }
}
