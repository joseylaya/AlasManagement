<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use RuntimeException;

class AuditSqliteForPostgresMigration extends Command
{
    protected $signature = 'alas:audit-sqlite {source : Absolute path to the SQLite snapshot} {--output= : Write the redacted JSON report here}';

    protected $description = 'Read-only integrity and reconciliation audit for an ALAS SQLite migration source';

    public function handle(): int
    {
        $source = realpath((string) $this->argument('source'));
        if (! $source || ! is_file($source)) {
            $this->error('SQLite source does not exist.');
            return self::FAILURE;
        }

        $pdo = new PDO('sqlite:'.$source, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $integrity = $pdo->query('PRAGMA integrity_check')->fetchAll(PDO::FETCH_COLUMN);
        $foreignKeys = $pdo->query('PRAGMA foreign_key_check')->fetchAll(PDO::FETCH_ASSOC);
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
            ->fetchAll(PDO::FETCH_COLUMN);

        $counts = [];
        $maxIds = [];
        foreach ($tables as $table) {
            $quoted = '"'.str_replace('"', '""', $table).'"';
            $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM {$quoted}")->fetchColumn();
            $columns = $pdo->query("PRAGMA table_info({$quoted})")->fetchAll(PDO::FETCH_ASSOC);
            if (collect($columns)->contains(fn ($column) => $column['name'] === 'id')) {
                $maxIds[$table] = $pdo->query("SELECT MAX(id) FROM {$quoted}")->fetchColumn();
            }
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'source' => [
                'basename' => basename($source),
                'bytes' => filesize($source),
                'modified_at' => date(DATE_ATOM, filemtime($source)),
                'sha256' => hash_file('sha256', $source),
            ],
            'integrity_check' => $integrity,
            'foreign_key_violation_count' => count($foreignKeys),
            'table_counts' => $counts,
            'table_max_ids' => $maxIds,
            'controls' => [
                'finance' => $this->financeControls($pdo, $tables),
                'compensation' => $this->compensationControls($pdo, $tables),
                'performance_points' => $this->pointControls($pdo, $tables),
                'promotion_activities' => $this->activityControls($pdo, $tables),
            ],
        ];

        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
        if ($output = $this->option('output')) {
            $directory = dirname((string) $output);
            if (! is_dir($directory) || ! is_writable($directory)) {
                throw new RuntimeException("Output directory is not writable: {$directory}");
            }
            file_put_contents((string) $output, $json, LOCK_EX);
            $this->info("Audit written to {$output}");
        } else {
            $this->line($json);
        }

        if ($integrity !== ['ok'] || $foreignKeys !== []) {
            $this->error('Source failed integrity checks. Do not migrate it.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function financeControls(PDO $pdo, array $tables): array
    {
        $controls = [];
        foreach (['cash_transactions', 'expenses', 'owner_drawals', 'owner_capital_injections'] as $table) {
            if (! in_array($table, $tables, true)) {
                continue;
            }
            $controls[$table] = $pdo->query("SELECT COUNT(*) AS rows, printf('%.2f', COALESCE(SUM(amount), 0)) AS amount FROM {$table}")
                ->fetch(PDO::FETCH_ASSOC);
        }
        return $controls;
    }

    private function compensationControls(PDO $pdo, array $tables): array
    {
        if (! in_array('compensation_records', $tables, true)) {
            return [];
        }
        return $pdo->query("SELECT type, status, COUNT(*) AS rows, printf('%.2f', COALESCE(SUM(amount), 0)) AS amount FROM compensation_records GROUP BY type, status ORDER BY type, status")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function pointControls(PDO $pdo, array $tables): array
    {
        if (! in_array('performance_point_entries', $tables, true)) {
            return [];
        }
        return $pdo->query('SELECT user_id, source_type, COUNT(*) AS rows, COALESCE(SUM(points), 0) AS points FROM performance_point_entries GROUP BY user_id, source_type ORDER BY user_id, source_type')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function activityControls(PDO $pdo, array $tables): array
    {
        if (! in_array('promotion_activities', $tables, true)) {
            return [];
        }
        return $pdo->query("SELECT user_id, status, COUNT(*) AS rows, printf('%.2f', COALESCE(SUM(approved_amount), 0)) AS approved_amount FROM promotion_activities GROUP BY user_id, status ORDER BY user_id, status")
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}
