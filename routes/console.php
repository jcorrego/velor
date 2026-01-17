<?php

use App\Jobs\SyncEcbExchangeRates;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule finance sync jobs
Schedule::job(new SyncEcbExchangeRates)
    ->daily()
    ->at('02:00') // Run at 2 AM daily (after ECB publishes rates, typically 4 PM CET)
    ->timezone('Europe/Madrid')
    ->name('sync-ecb-exchange-rates')
    ->withoutOverlapping(10); // Don't overlap within 10 minutes
