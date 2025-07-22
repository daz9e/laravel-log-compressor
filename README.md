Laravel Log Compressor

A Laravel package to compress old log files and delete expired compressed logs, either manually or via a scheduled task.

Requirements





PHP 7.4 or higher



Laravel 6.x, 7.x, 8.x, 9.x, 10.x, or 11.x

Installation





Require the package:

composer require daz9e/laravel-log-compressor



Add configuration to config/logging.php:

return [
'default' => env('LOG_CHANNEL', 'stack'),
'compress_days' => env('LOG_COMPRESS_DAYS', 2),
'compress_schedule' => env('LOG_COMPRESS_SCHEDULE', true),
'channels' => [
'stack' => [
'driver' => 'stack',
'channels' => ['daily'],
'ignore_exceptions' => false,
],
'daily' => [
'driver' => 'daily',
'path' => storage_path('logs/laravel.log'),
'level' => env('LOG_LEVEL', 'debug'),
'days' => env('LOG_DAILY_DAYS', 14),
],
],
];



Optionally, configure settings in .env:

LOG_COMPRESS_DAYS=2
LOG_DAILY_DAYS=14
LOG_COMPRESS_SCHEDULE=true

Usage

Manual Execution

Run the command to compress logs:

php artisan logs:compress [days]

Scheduled Execution

The command is automatically scheduled to run daily by default. To disable scheduling, set LOG_COMPRESS_SCHEDULE=false in your .env file.

License

MIT