# Laravel Log Compressor

[![tests](https://github.com/daz9e/laravel-log-compressor/actions/workflows/tests.yml/badge.svg)](https://github.com/daz9e/laravel-log-compressor/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/daz9e/laravel-log-compressor)](https://packagist.org/packages/daz9e/laravel-log-compressor)
[![License](https://img.shields.io/packagist/l/daz9e/laravel-log-compressor)](LICENSE)

Gzips rotated Laravel log files (`*-YYYY-MM-DD.log`) and removes stale archives, so `storage/logs` stops eating your disk.

## Installation

```bash
composer require daz9e/laravel-log-compressor
```

The service provider is auto-discovered, and the command is scheduled to run daily.

## Usage

```bash
php artisan logs:compress [days]
```

- Compresses dated `.log` files in `storage/logs` (including subdirectories) that are older than `days`, counted from the newest log file. The original file is removed only after the `.gz` is written successfully.
- Deletes `.log.gz` archives older than `logging.channels.daily.days` (default `14`).
- Files without a date in the name, such as `laravel.log`, are never touched.

## Configuration

Optional keys in `config/logging.php`:

```php
'compress_days' => env('LOG_COMPRESS_DAYS', 2),  // days to keep logs uncompressed
'compress_schedule' => true,                      // set to false to disable the daily schedule
```

Archive retention reuses `logging.channels.daily.days`.

The daily run needs the Laravel scheduler:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Testing

```bash
composer install
composer test
```

## License

MIT
