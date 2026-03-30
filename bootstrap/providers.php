<?php

use App\Providers\AppHealthServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\JetstreamServiceProvider;

return [
    AppServiceProvider::class,
    AppHealthServiceProvider::class,
    AuthServiceProvider::class,
    FortifyServiceProvider::class,
    JetstreamServiceProvider::class,
    AdminPanelProvider::class,
];
