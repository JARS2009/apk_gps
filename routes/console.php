<?php

use App\Console\Commands\GpsListenCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Registrar comando GPS listener (TCP servidor para rastreadores SinoTrack GT06)
Artisan::resolve(GpsListenCommand::class);
