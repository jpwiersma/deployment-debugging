<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    Cache::put('diagnostics:last_cron_run', now(), now()->addDay());
})->everyMinute()->name('diagnostics:heartbeat');
