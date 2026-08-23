<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled maintenance (requires a single cron: * * * * * php artisan schedule:run)
|--------------------------------------------------------------------------
*/

// Housekeeping for the database queue driver.
Schedule::command('queue:prune-batches --hours=48')->dailyAt('03:10');
Schedule::command('queue:prune-failed --hours=720')->weeklyOn(1, '03:20');
