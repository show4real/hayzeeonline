<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Laravel 11+ defines the schedule here rather than in App\Console\Kernel.
|
*/

// Keeps the Meilisearch product index in step with the products table.
// withoutOverlapping, because a full re-import can outlast the five-minute
// window on a large catalogue and two importers would fight over the index.
Schedule::command('scout:import', ['App\Models\Product'])
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
