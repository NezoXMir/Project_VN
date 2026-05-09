<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ежедневное напоминание в 9:00 по таймзоне приложения (Europe/Moscow).
// Включается автоматически на сервере, где настроен `* * * * * php artisan schedule:run`.
Schedule::command('reminders:send')
    ->dailyAt('09:00')
    ->timezone(config('app.timezone'));
