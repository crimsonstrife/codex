<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AuthSettings extends Settings
{
    public bool $allowRegistration;

    public static function group(): string
    {
        return 'auth';
    }
}
