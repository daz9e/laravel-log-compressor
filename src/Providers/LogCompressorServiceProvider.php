<?php

namespace Daz9e\LaravelLogCompressor\Providers;

use Illuminate\Support\ServiceProvider;
use Daz9e\LaravelLogCompressor\Console\Commands\CompressOldLogs;
use Illuminate\Console\Scheduling\Schedule;

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
                $this->scheduleDailyCompression();
            }
        }
    }

    private function scheduleDailyCompression()
    {
        $register = function (Schedule $schedule) {
            $schedule->command('logs:compress')->daily();
        };

        $this->app->afterResolving(Schedule::class, $register);

        if ($this->app->resolved(Schedule::class)) {
            $register($this->app->make(Schedule::class));
        }
    }
}
