<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\DatabaseBackupService;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaintenanceController extends Controller
{
    public function downloadDatabaseBackup(DatabaseBackupService $backups): BinaryFileResponse
    {
        try {
            $path = $backups->createSqliteSnapshot();
        } catch (RuntimeException $exception) {
            abort(503, $exception->getMessage());
        }

        ActivityLogService::log(
            'Database Backup Downloaded',
            'Downloaded a maintenance SQLite backup.',
            null,
            ['filename' => basename($path)],
        );

        return response()->download($path, 'alas-backup-'.now()->format('Ymd-His').'.sqlite', [
            'Content-Type' => 'application/vnd.sqlite3',
            'Cache-Control' => 'no-store, private',
        ])->deleteFileAfterSend(true);
    }
}
