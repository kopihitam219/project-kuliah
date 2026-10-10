<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    App\Providers\SecurityServiceProvider::class,
    App\Providers\CloudServiceProvider::class,
    App\Providers\ThemeServiceProvider::class,
];
