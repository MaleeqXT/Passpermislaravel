<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


use App\Console\Commands\GenerateFactureCommand;
// use App\Console\Commands\GenerateSitemapCommand;
use App\Console\Commands\SendCpfEmailsCommand;
use App\Console\Commands\GenerateRatingMonitorCommand;
use App\Console\Commands\UpdatePassedCancellations;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// $schedule->command('inspire')->hourly();
//   $schedule->command(GenerateSitemapCommand::class)->daily();
//   $schedule->command(GenerateFactureCommand::class)->monthly();
//   $schedule->command(GenerateRatingMonitorCommand::class)->everyMinute();
//   $schedule->command(SendCpfEmailsCommand::class)->daily();
//   $schedule->command(StoreSettingPlanningMoniteurCommand::class)->everyMinute();
//Schedule::command(UpdatePassedCancellations::class)->everyMinute();
Schedule::command(GenerateFactureCommand::class)->monthly();
Schedule::command(GenerateRatingMonitorCommand::class)->everyMinute();
//Schedule::command(SendCpfEmailsCommand::class)->daily();
// Schedule::call(new StoreSettingPlanningMoniteurCommand)->everyMinute();
