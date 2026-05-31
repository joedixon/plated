<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Drip a fresh AI-generated dish onto the pass on a fixed cadence. The interval
| (seconds) is mapped to the nearest sub-minute scheduler frequency. plated:plate
| only enqueues the GenerateDish job, so it returns instantly and runs in the
| background, keeping the schedule loop free for the next tick.
*/
$interval = (int) config('plated.menu_interval', 30);

$dripDishes = Schedule::command('plated:plate 1');

match (true) {
    $interval <= 5 => $dripDishes->everyFiveSeconds(),
    $interval <= 10 => $dripDishes->everyTenSeconds(),
    $interval <= 15 => $dripDishes->everyFifteenSeconds(),
    $interval <= 20 => $dripDishes->everyTwentySeconds(),
    $interval <= 30 => $dripDishes->everyThirtySeconds(),
    default => $dripDishes->everyMinute(),
};

$dripDishes->runInBackground();
