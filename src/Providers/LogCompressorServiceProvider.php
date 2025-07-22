<?php

namespace Daz9e\LaravelLogCompressor\Providers;

use Illuminate\Support\ServiceProvider;
use Daz9e\LaravelLogCompressor\Console\Commands\CompressOldLogs;

class LogCompressorServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CompressOldLogs::class,
            ]);

            if (config('logging.compress_schedule', true)) {
                $this->app->booted(function () {
                    $schedule = $this->app->make('Illuminate\Console\Scheduling\Schedule');
                    $schedule->command('logs:compress')->daily();
                });
            }
        }
    }
}