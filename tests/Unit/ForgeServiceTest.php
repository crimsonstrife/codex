<?php

namespace Tests\Unit;

use App\Services\ForgeService;
use ReflectionClass;
use Tests\TestCase;

class ForgeServiceTest extends TestCase
{
    public function test_it_falls_back_to_standard_forge_credentials_when_m2m_credentials_are_missing(): void
    {
        config()->set('codex.forge.url', 'https://forge.example.com');
        config()->set('codex.forge.client_id', 'oauth-client-id');
        config()->set('codex.forge.client_secret', 'oauth-client-secret');
        config()->set('codex.forge.m2m_client_id', '');
        config()->set('codex.forge.m2m_client_secret', '');

        $service = new ForgeService();

        $this->assertTrue($service->isConfigured());
        $this->assertSame('oauth-client-id', $this->readProperty($service, 'clientId'));
        $this->assertSame('oauth-client-secret', $this->readProperty($service, 'clientSecret'));
    }

    public function test_it_prefers_dedicated_m2m_credentials_when_available(): void
    {
        config()->set('codex.forge.url', 'https://forge.example.com');
        config()->set('codex.forge.client_id', 'oauth-client-id');
        config()->set('codex.forge.client_secret', 'oauth-client-secret');
        config()->set('codex.forge.m2m_client_id', 'm2m-client-id');
        config()->set('codex.forge.m2m_client_secret', 'm2m-client-secret');

        $service = new ForgeService();

        $this->assertSame('m2m-client-id', $this->readProperty($service, 'clientId'));
        $this->assertSame('m2m-client-secret', $this->readProperty($service, 'clientSecret'));
    }

    private function readProperty(ForgeService $service, string $property): mixed
    {
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($service);
    }
}
