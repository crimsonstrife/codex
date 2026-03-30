<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ForgeSettings extends Settings
{
    public bool $enabled;

    public string $url;

    public bool $disableTlsVerification;

    public static function group(): string
    {
        return 'forge';
    }
}
