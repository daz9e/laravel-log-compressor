<?php

namespace Daz9e\LaravelLogCompressor\Tests;

use Carbon\Carbon;
use Daz9e\LaravelLogCompressor\Providers\LogCompressorServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class CompressOldLogsTest extends TestCase
{
    private string $storagePath;

    protected function getPackageProviders($app)
    {
        return [LogCompressorServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $this->storagePath = sys_get_temp_dir() . '/log-compressor-' . uniqid();
        File::ensureDirectoryExists($this->storagePath . '/logs');

        $app->useStoragePath($this->storagePath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storagePath);

        parent::tearDown();
    }

    public function test_compresses_logs_older_than_given_days_relative_to_newest_log(): void
    {
        $old = 'laravel-' . $this->daysAgo(6) . '.log';
        $recent = 'laravel-' . $this->daysAgo(4) . '.log';
        $newest = 'laravel-' . $this->daysAgo(3) . '.log';
        $this->writeLog($old, 'old entry');
        $this->writeLog($recent, 'recent entry');
        $this->writeLog($newest, 'newest entry');

        $this->artisan('logs:compress', ['days' => 2])->assertExitCode(0);

        $this->assertFileDoesNotExist($this->logPath($old));
        $this->assertSame('old entry', gzdecode(file_get_contents($this->logPath($old . '.gz'))));
        $this->assertFileExists($this->logPath($recent));
        $this->assertFileExists($this->logPath($newest));
    }

    public function test_uses_compress_days_config_when_no_argument_given(): void
    {
        config(['logging.compress_days' => 5]);

        $old = 'laravel-' . $this->daysAgo(7) . '.log';
        $recent = 'laravel-' . $this->daysAgo(4) . '.log';
        $this->writeLog($old, 'old entry');
        $this->writeLog($recent, 'recent entry');
        $this->writeLog('laravel-' . $this->daysAgo(1) . '.log', 'newest entry');

        $this->artisan('logs:compress')->assertExitCode(0);

        $this->assertFileExists($this->logPath($old . '.gz'));
        $this->assertFileExists($this->logPath($recent));
    }

    public function test_compresses_logs_in_subdirectories(): void
    {
        $old = 'integrations/sync-' . $this->daysAgo(5) . '.log';
        $this->writeLog($old, 'old entry');
        $this->writeLog('laravel-' . $this->daysAgo(1) . '.log', 'newest entry');

        $this->artisan('logs:compress', ['days' => 2])->assertExitCode(0);

        $this->assertFileExists($this->logPath($old . '.gz'));
    }

    public function test_ignores_files_without_date_in_name(): void
    {
        $this->writeLog('laravel.log', 'single file log');

        $this->artisan('logs:compress', ['days' => 0])->assertExitCode(0);

        $this->assertFileExists($this->logPath('laravel.log'));
        $this->assertFileDoesNotExist($this->logPath('laravel.log.gz'));
    }

    public function test_deletes_archives_older_than_daily_channel_retention(): void
    {
        config(['logging.channels.daily.days' => 14]);

        $expired = 'laravel-' . $this->daysAgo(20) . '.log.gz';
        $fresh = 'laravel-' . $this->daysAgo(5) . '.log.gz';
        $this->writeLog($expired, gzencode('expired'));
        $this->writeLog($fresh, gzencode('fresh'));

        $this->artisan('logs:compress')->assertExitCode(0);

        $this->assertFileDoesNotExist($this->logPath($expired));
        $this->assertFileExists($this->logPath($fresh));
    }

    public function test_schedules_command_daily(): void
    {
        $event = collect($this->app->make(Schedule::class)->events())
            ->first(function ($event) {
                return str_contains($event->command, 'logs:compress');
            });

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
    }

    private function daysAgo(int $days): string
    {
        return Carbon::now()->subDays($days)->toDateString();
    }

    private function writeLog(string $name, string $contents): void
    {
        File::ensureDirectoryExists(dirname($this->logPath($name)));
        File::put($this->logPath($name), $contents);
    }

    private function logPath(string $name): string
    {
        return $this->storagePath . '/logs/' . $name;
    }
}
