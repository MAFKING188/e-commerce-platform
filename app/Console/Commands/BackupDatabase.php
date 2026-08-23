<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Nightly database backup: mysqldump -> gzip -> storage/app/backups,
 * retaining the newest N files. Scheduled in routes/console.php.
 */
class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database {--keep=7}';

    protected $description = 'Dump the MySQL database to storage/app/backups (gzip) and prune old dumps';

    public function handle(): int
    {
        $database = config('database.connections.' . config('database.default') . '.database');
        if (! is_string($database) || Str::contains(config('database.default'), 'sqlite')) {
            $this->error('Backup only supports the mysql connection.');

            return self::FAILURE;
        }

        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $dir . '/db-' . now()->format('Ymd-His') . '.sql.gz';

        $username = (string) config('database.connections.mysql.username');
        $password = (string) config('database.connections.mysql.password');
        $host = (string) config('database.connections.mysql.host');

        $env = [
            'MYSQL_PWD' => $password, // never on argv (visible in ps)
        ];

        $result = Process::env($env)->timeout(300)->run(
            "mysqldump --host={$host} --user={$username} --single-transaction --quick {$database} | gzip > " . escapeshellarg($file)
        );

        if (! $result->successful() || ! file_exists($file) || filesize($file) < 100) {
            @unlink($file);
            $this->error('Backup failed: ' . $result->errorOutput());

            return self::FAILURE;
        }

        $this->pruneOldBackups($dir, max(1, (int) $this->option('keep')));

        $size = round(filesize($file) / 1024, 1);
        $this->info("Backup written: {$file} ({$size} KB)");

        return self::SUCCESS;
    }

    private function pruneOldBackups(string $dir, int $keep): void
    {
        $files = glob($dir . '/db-*.sql.gz');

        if (count($files) <= $keep) {
            return;
        }

        sort($files); // timestamped names sort chronologically
        foreach (array_slice($files, 0, count($files) - $keep) as $old) {
            @unlink($old);
        }
    }
}