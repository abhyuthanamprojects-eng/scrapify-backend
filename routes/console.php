<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\PickupAssignmentService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pickups:auto-reschedule-overdue', function (PickupAssignmentService $service) {
    $count = $service->autoRescheduleOverduePickups();

    $this->info("Auto-rescheduled {$count} overdue pickups.");
})->purpose('Auto-reschedule overdue pickups to the next day');
