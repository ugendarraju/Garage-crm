<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\SystemSettingService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automated Periodic CRM Backup
Schedule::call(function () {
    $frequency = SystemSettingService::get('backup_frequency', 'biweekly');
    if ($frequency === 'disabled') {
        return;
    }
    Artisan::call('crm:backup', ['--label' => 'Automated Scheduled Backup']);
})->dailyAt('02:00')->when(function () {
    $frequency = SystemSettingService::get('backup_frequency', 'biweekly');
    if ($frequency === 'disabled') return false;
    if ($frequency === 'weekly') return now()->isMonday();
    if ($frequency === 'biweekly') return in_array(now()->day, [1, 15]);
    if ($frequency === 'monthly') return now()->day === 1;
    return false;
});
