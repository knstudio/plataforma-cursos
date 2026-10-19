<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Needs the server cron: * * * * * php artisan schedule:run
// Every 10 seconds, so a message due now goes out within seconds.
Schedule::command('funil:disparar')->everyTenSeconds()->withoutOverlapping();
