<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\NotificationService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:dispatch-scheduled', function () {
    $count = NotificationService::dispatchScheduledAnnouncements();
    $this->info("Delivered {$count} scheduled announcement(s).");
})->purpose('Deliver scheduled team announcements');

Schedule::command('notifications:dispatch-scheduled')->everyMinute()->withoutOverlapping();
