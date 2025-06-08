<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:clear-cache', function () {
    $this->call('cache:clear');
    $this->call('config:clear');
    $this->call('route:clear');
    $this->call('view:clear');
    $this->info('Application cache cleared successfully.');
})->purpose('Clear application cache, config, routes, and views');

Artisan::command('app:optimize', function () {
    $this->call('optimize');
    $this->info('Application optimized successfully.');
})->purpose('Optimize the application by caching routes and configurations');

Schedule::command('app:process-recurring-transactions')
    ->everyFiveSeconds() // Schedule to run daily at midnight
    ->withoutOverlapping() // Prevent overlapping executions
    ->onFailure(function () {
        Log::error('Failed to process recurring transactions.');
    })
    ->onSuccess(function () {
        Log::info('Successfully processed recurring transactions.');
    });