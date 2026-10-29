<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orders:expire-reservations')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

Schedule::command('receipts:prune-orphans')
    ->dailyAt('03:15')
    ->withoutOverlapping(60);
