<?php

namespace App\Support;

use App\Settings\AuthSettings;
use App\Settings\CodexSettings;
use App\Settings\ForgeSettings;
use Throwable;

class CodexRuntimeConfig
{
    public static function registrationEnabled(): bool
    {
        return (bool) static::settingsValue(
            AuthSettings::class,
            'allowRegistration',
            true,
        );
    }

    public static function defaultPageContentType(): string
    {
        $value = (string) static::settingsValue(
            CodexSettings::class,
            'defaultPageContentType',
            (string) config('codex.editor.default_content_type', 'markdown'),
        );

        return in_array($value, ['markdown', 'richtext'], true) ? $value : 'markdown';
    }

    public static function drawioUrl(): string
    {
        return (string) static::settingsValue(
            CodexSettings::class,
            'drawioUrl',
            (string) config('codex.diagrams.drawio_url', 'https://embed.diagrams.net'),
        );
    }

    public static function forgeEnabled(): bool
    {
        return (bool) static::settingsValue(
            ForgeSettings::class,
            'enabled',
            (bool) config('codex.forge.enabled', false),
        );
    }

    public static function forgeUrl(): string
    {
        return rtrim((string) static::settingsValue(
            ForgeSettings::class,
            'url',
            (string) config('codex.forge.url', ''),
        ), '/');
    }

    public static function forgeDisableTlsVerification(): bool
    {
        return (bool) static::settingsValue(
            ForgeSettings::class,
            'disableTlsVerification',
            (bool) config('codex.forge.disable_tls_verification', false),
        );
    }

    public static function forgeSsoAvailable(): bool
    {
        return static::forgeEnabled()
            && filled(static::forgeUrl())
            && filled((string) config('codex.forge.client_id'))
            && filled((string) config('codex.forge.client_secret'));
    }

    public static function forgeApiConfigured(): bool
    {
        return static::forgeEnabled()
            && filled(static::forgeUrl())
            && filled(static::forgeApiClientId())
            && filled(static::forgeApiClientSecret());
    }

    public static function forgeApiClientId(): string
    {
        return (string) (config('codex.forge.m2m_client_id') ?: config('codex.forge.client_id', ''));
    }

    public static function forgeApiClientSecret(): string
    {
        return (string) (config('codex.forge.m2m_client_secret') ?: config('codex.forge.client_secret', ''));
    }

    protected static function settingsValue(string $settingsClass, string $property, mixed $fallback): mixed
    {
        try {
            return app($settingsClass)->{$property};
        } catch (Throwable) {
            return $fallback;
        }
    }
}
