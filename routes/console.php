<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(
        Inspiring::quote(),
    );
})->purpose(
    'Display an inspiring quote',
);

Schedule::command(
    'mock-board:finalize-expired',
)
    ->everyMinute()
    ->withoutOverlapping(10);

Schedule::command(
    'model:prune',
)
    ->daily()
    ->withoutOverlapping();

Schedule::command('system:heartbeat')
    ->everyMinute()
    ->withoutOverlapping(5);
