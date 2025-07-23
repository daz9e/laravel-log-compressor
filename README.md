

# Log Compression Command

## Usage
Run the command to compress log files older than a specified number of days and delete old compressed logs.

```bash
php artisan logs:compress [days]
```

- **`days`** (optional): Number of days to keep logs uncompressed. Defaults to `LOG_COMPRESS_DAYS` in `.env` or `2` days.

### Example
```bash
php artisan logs:compress 5
```
Compresses logs older than 5 days and deletes `.gz` files older than 14 days (configurable in `config/logging.php`).

## Configuration
In `config/logging.php`:
- **`compress_days`**: Days to keep logs uncompressed (default: `2`). Override via `.env`:
  ```env
  LOG_COMPRESS_DAYS=5
  ```
- **`logging.channels.daily.days`**: Retention period for compressed logs (default: `14` days).

## Scheduling
Scheduled to run daily in `routes/console.php`:
```php
Schedule::command('logs:compress')->daily();
```

Ensure the Laravel scheduler is set up:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

