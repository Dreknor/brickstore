<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic BrickLink order synchronization - runs every 5 minutes
// --direct: führt den Sync direkt aus (kein Queue-Umweg), besser für Shared Hosting
Schedule::command('bricklink:sync-orders --days=20 --direct')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onFailure(function () {
        Log::error('BrickLink order sync failed');
    });

// Schedule inventory image caching - runs every minute
// Dispatcht Jobs in die images-Queue; der queue:work-Aufruf unten holt sie ab
Schedule::command('inventory:process-image-queue')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onFailure(function () {
        Log::error('Inventory image caching queue processing failed');
    });

// Beide Queues abarbeiten: default (Bestellungen, Sync) + images (Bild-Caching)
// --stop-when-empty: Prozess beendet sich automatisch wenn Queue leer ist (Shared-Hosting-kompatibel)
// --max-time=55: max. 55 Sek. laufen, damit der nächste Cron-Aufruf nicht blockiert wird
Schedule::command('queue:work --stop-when-empty --queue=default,images --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(2); // verhindert parallele Prozesse, max. 2 Min. Lock
