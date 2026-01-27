<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic BrickLink order synchronization - runs every 5 minutes
// Only syncs recent orders (last 2 days) to keep it fast and catch new orders quickly
Schedule::command('bricklink:sync-orders --days=2')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Log::error('BrickLink order sync failed');
    });

// Schedule inventory image caching - runs every minute
Schedule::command('inventory:process-image-queue')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Log::error('Inventory image caching queue processing failed');
    });

