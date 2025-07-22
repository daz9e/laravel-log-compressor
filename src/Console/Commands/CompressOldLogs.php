<?php

namespace Daz9e\LaravelLogCompressor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CompressOldLogs extends Command
{
    private const LOG_FILE_PATTERN = '/laravel-(\d{4}-\d{2}-\d{2})\.log$/';
    private const GZ_FILE_PATTERN = '/laravel-(\d{4}-\d{2}-\d{2})\.log.gz$/';

    protected $signature = 'logs:compress {days? : Number of days to keep logs uncompressed}';

    protected $description = 'Compress log files older than the specified number of days (defaults to logging.compress_days config value)';

    public function handle()
    {
        $days = (int)($this->argument('days') ?? config('logging.compress_days', 2));

        $logFiles = $this->getLogFiles();
        $latestDate = $this->getLatestLogDate($logFiles) ?? Carbon::now();
        $cutoffDate = $latestDate->copy()->subDays($days);

        $this->info("Using reference date: {$latestDate->toDateString()}");
        $this->info("Compressing logs older than {$days} days ({$cutoffDate->toDateString()})");

        $toCompress = $logFiles->filter(function ($file) use ($cutoffDate) {
            return $this->shouldCompress($file, $cutoffDate);
        });
        $toCompress->each(function ($file) {
            $this->compressFile($file);
        });

        $this->info("Compressed {$toCompress->count()} log file(s).");

        $deletionCutoffDate = Carbon::now()->subDays(config('logging.channels.daily.days', 14));
        $deletedCount = $this->deleteOldFiles($deletionCutoffDate);

        $this->info("Deleted {$deletedCount} files");

        return defined('CommandAlias::SUCCESS') ? CommandAlias::SUCCESS : 0;
    }

    protected function getLogFiles()
    {
        return collect(File::files(storage_path('logs')))
            ->filter(function ($file) {
                return preg_match(self::LOG_FILE_PATTERN, $file->getFilename());
            });
    }

    protected function getLatestLogDate(Collection $files)
    {
        return $files
            ->map(function ($file) {
                if (preg_match(self::LOG_FILE_PATTERN, $file->getFilename(), $matches)) {
                    try {
                        return Carbon::createFromFormat('Y-m-d', $matches[1]) ? Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay() : null;
                    } catch (\Exception $e) {
                        return null;
                    }
                }
                return null;
            })
            ->filter()
            ->max();
    }

    protected function shouldCompress($file, Carbon $cutoffDate)
    {
        if (preg_match(self::LOG_FILE_PATTERN, $file->getFilename(), $matches)) {
            try {
                $fileDate = Carbon::createFromFormat('Y-m-d', $matches[1]);
                return $fileDate && $fileDate->lt($cutoffDate);
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    protected function compressFile($file)
    {
        $filename = $file->getFilename();
        $this->line("Compressing {$filename}...");

        $sourcePath = $file->getPathname();
        $gzPath = $sourcePath . '.gz';

        $input = @fopen($sourcePath, 'rb');
        $output = @gzopen($gzPath, 'wb9');

        if (!$input || !$output) {
            $this->error("✖ Unable to open files for compression: {$filename}");
            return;
        }

        while (!feof($input)) {
            gzwrite($output, fread($input, 1024 * 512));
        }

        fclose($input);
        gzclose($output);

        if (File::exists($gzPath) && File::size($gzPath) > 0) {
            File::delete($sourcePath);
            $this->line("✔ Compressed and removed original: {$filename}");
        } else {
            File::delete($gzPath);
            $this->error("✖ Failed to compress {$filename}");
        }
    }

    protected function deleteOldFiles(Carbon $cutoffDate)
    {
        $path = storage_path('logs');
        $deletedCount = 0;

        $files = collect(File::files($path))
            ->filter(function ($file) {
                return preg_match(self::GZ_FILE_PATTERN, $file->getFilename());
            })
            ->filter(function ($file) use ($cutoffDate) {
                if (preg_match(self::GZ_FILE_PATTERN, $file->getFilename(), $matches)) {
                    try {
                        $fileDate = Carbon::createFromFormat('Y-m-d', $matches[1]);
                        return $fileDate && $fileDate->lt($cutoffDate);
                    } catch (\Exception $e) {
                        return false;
                    }
                }
                return false;
            });

        foreach ($files as $file) {
            $gzFile = $file->getPathname();
            if (File::exists($gzFile)) {
                try {
                    File::delete($gzFile);
                    $deletedCount++;
                } catch (\Exception $e) {
                    $this->error("Delete file error: {$file->getFilename()}: " . $e->getMessage());
                }
            }
        }

        return $deletedCount;
    }
}