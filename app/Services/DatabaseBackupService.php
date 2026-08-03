<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class DatabaseBackupService
{
    /** Create a transactionally consistent SQLite copy outside the public web root. */
    public function createSqliteSnapshot(): string
    {
        $database = (string) config('database.connections.sqlite.database');

        if ($database === ':memory:' || $database === '' || !File::exists($database)) {
            throw new RuntimeException('The configured SQLite database file is unavailable for backup.');
        }

        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory, 0700, true);

        $path = $directory.'/alas-backup-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sqlite';
        $quotedPath = str_replace("'", "''", $path);

        // VACUUM INTO creates a consistent snapshot even while the application
        // is running, unlike copying the SQLite file directly.
        DB::connection('sqlite')->unprepared("VACUUM INTO '{$quotedPath}'");

        return $path;
    }
}
