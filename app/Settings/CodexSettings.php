<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CodexSettings extends Settings
{
    public string $defaultPageContentType;

    public string $drawioUrl;

    public static function group(): string
    {
        return 'codex';
    }
}
