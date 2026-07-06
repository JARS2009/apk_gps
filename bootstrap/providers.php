<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\NativeAppServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    NativeAppServiceProvider::class,
];
