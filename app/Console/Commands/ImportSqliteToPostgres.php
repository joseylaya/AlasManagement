<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use RuntimeException;
use Throwable;

class ImportSqliteToPostgres extends Command
{
    protected $signature = 'alas:import-sqlite-to-postgres
        {source : Absolute path to the frozen SQLite snapshot}
        {--write : Perform the import; without this flag the command only validates}
        {--force : Skip the interactive production confirmation}';

    protected $description = 'Import a frozen ALAS SQLite snapshot into an empty migrated PostgreSQL database';

    private const EXCLUDED_TABLES = [
        'migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
    ];

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->error('Refusing import: the configured destination is not PostgreSQL.');
            return self::FAILURE;
        }

        $sourcePath = realpath((string) $this->argument('source'));
        if (! $sourcePath || ! is_file($sourcePath)) {
            $this->error('SQLite source does not exist.');
            return self::FAILURE;
        }

        $source = new PDO('sqlite:'.$sourcePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        if ($source->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
            $this->error('SQLite integrity_check failed.');
            return self::FAILURE;
        }
        if ($source->query('PRAGMA foreign_key_check')->fetchAll() !== []) {
            $this->error('SQLite contains foreign-key violations.');
            return self::FAILURE;
        }

        $tables = $source->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
            ->fetchAll(PDO::FETCH_COLUMN);
        $tables = array_values(array_filter($tables, fn ($table) => ! in_array($table, self::EXCLUDED_TABLES, true) && Schema::hasTable($table)));

        $this->table(['Source', 'SHA-256', 'Importable tables'], [[basename($sourcePath), hash_file('sha256', $sourcePath), count($tables)]]);
        if (! $this->option('write')) {
            $this->warn('Validation only. Re-run with --write after reviewing the SQLite audit report.');
            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm('Import into the configured EMPTY PostgreSQL database?')) {
            return self::FAILURE;
        }

        $replaceSeededTables = [];
        foreach ($tables as $table) {
            if (DB::table($table)->limit(1)->exists()) {
                if ($table === 'financial_accounts' && $this->seededFinancialAccountsMatch($source)) {
                    $replaceSeededTables[] = $table;
                    continue;
                }
                $this->error("Destination table {$table} is not empty. No data was written.");
                return self::FAILURE;
            }
        }

        try {
            DB::transaction(function () use ($source, $tables, $replaceSeededTables) {
                // The import preserves already-validated legacy IDs and may contain nullable
                // circular audit links. Disable triggers only inside this transaction, then
                // run PostgreSQL orphan checks before commit.
                DB::statement("SET LOCAL session_replication_role = 'replica'");

                foreach ($replaceSeededTables as $table) {
                    DB::table($table)->delete();
                }

                foreach ($tables as $table) {
                    $targetColumns = array_flip(Schema::getColumnListing($table));
                    $sourceColumns = $source->query('PRAGMA table_info("'.str_replace('"', '""', $table).'")')->fetchAll(PDO::FETCH_ASSOC);
                    $columns = array_values(array_filter(array_column($sourceColumns, 'name'), fn ($column) => isset($targetColumns[$column])));
                    if ($columns === []) {
                        continue;
                    }

                    $quoted = '"'.str_replace('"', '""', $table).'"';
                    $orderBy = in_array('id', array_column($sourceColumns, 'name'), true) ? ' ORDER BY id' : '';
                    $statement = $source->query("SELECT * FROM {$quoted}{$orderBy}");
                    $batch = [];
                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        $batch[] = array_intersect_key($row, array_flip($columns));
                        if (count($batch) === 250) {
                            DB::table($table)->insert($batch);
                            $batch = [];
                        }
                    }
                    if ($batch !== []) {
                        DB::table($table)->insert($batch);
                    }
                    $this->line("Imported {$table}: ".DB::table($table)->count());
                }

                DB::statement("SET LOCAL session_replication_role = 'origin'");
                $this->assertCountsMatch($source, $tables);
                $this->assertNoPostgresOrphans();
                $this->resetSequences($tables);
            }, 1);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Import rolled back: '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Import committed. Run the SQLite audit and PostgreSQL reconciliation reports before cutover.');
        return self::SUCCESS;
    }

    private function assertCountsMatch(PDO $source, array $tables): void
    {
        foreach ($tables as $table) {
            $quoted = '"'.str_replace('"', '""', $table).'"';
            $sourceCount = (int) $source->query("SELECT COUNT(*) FROM {$quoted}")->fetchColumn();
            $targetCount = DB::table($table)->count();
            if ($sourceCount !== $targetCount) {
                throw new RuntimeException("Count mismatch for {$table}: SQLite {$sourceCount}, PostgreSQL {$targetCount}");
            }
        }
    }

    private function assertNoPostgresOrphans(): void
    {
        $relations = DB::select(<<<'SQL'
            SELECT tc.table_name, kcu.column_name, ccu.table_name AS parent_table, ccu.column_name AS parent_column
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name AND tc.constraint_schema = kcu.constraint_schema
            JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name AND ccu.constraint_schema = tc.constraint_schema
            WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_schema = current_schema()
        SQL);

        foreach ($relations as $relation) {
            $sql = sprintf(
                'SELECT COUNT(*) AS aggregate FROM "%s" child LEFT JOIN "%s" parent ON child."%s" = parent."%s" WHERE child."%s" IS NOT NULL AND parent."%s" IS NULL',
                $relation->table_name, $relation->parent_table, $relation->column_name,
                $relation->parent_column, $relation->column_name, $relation->parent_column
            );
            if ((int) DB::selectOne($sql)->aggregate !== 0) {
                throw new RuntimeException("Orphan rows found: {$relation->table_name}.{$relation->column_name}");
            }
        }
    }

    private function resetSequences(array $tables): void
    {
        foreach ($tables as $table) {
            if (! Schema::hasColumn($table, 'id')) {
                continue;
            }
            DB::statement(
                "SELECT setval(pg_get_serial_sequence(?, 'id'), COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1), (SELECT COUNT(*) > 0 FROM \"{$table}\"))",
                [$table]
            );
        }
    }

    private function seededFinancialAccountsMatch(PDO $source): bool
    {
        $sourceRows = $source->query('SELECT id, name, type, is_active FROM financial_accounts ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $targetRows = DB::table('financial_accounts')->orderBy('id')->get(['id', 'name', 'type', 'is_active']);
        if (count($sourceRows) !== $targetRows->count()) {
            return false;
        }

        foreach ($sourceRows as $index => $sourceRow) {
            $target = $targetRows[$index];
            if ((int) $sourceRow['id'] !== (int) $target->id
                || $sourceRow['name'] !== $target->name
                || $sourceRow['type'] !== $target->type
                || (bool) $sourceRow['is_active'] !== (bool) $target->is_active) {
                return false;
            }
        }

        return true;
    }
}
