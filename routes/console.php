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

// Prompts both sides of a finished shift to review each other. Runs off the
// same per-minute cron as the queue worker above; the command itself marks
// each shift as reminded, so running often is harmless.
Schedule::command('reviews:send-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Reviews stay private for a few days so a low rating can't be traced back to
// a specific shift; this flips them public once that wait is over.
Schedule::command('reviews:publish')
    ->hourly()
    ->withoutOverlapping();
