<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Processes queued jobs (push notifications, etc.) in short batches instead of
// a long-running `queue:work` daemon — needed on shared hosting (Hostinger),
// where there's no way to keep a background process alive. Runs every minute
// via the single `schedule:run` cron entry, stops itself once the queue is
// empty, and --max-time keeps it from bleeding into the next minute's run.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
