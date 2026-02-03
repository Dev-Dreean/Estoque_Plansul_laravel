<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use PDOException;

class SyncRemoteTables extends Command
{
    protected $signature = 'sync:remote {table=all} {--dry-run} {--chunk=500}';

    protected $description = 'Sync TABFANTASIA and funcionarios from remote MySQL into the local database (insert only).';

    private const TABLES = [
        'tabfant' => [
            'source_env' => 'TABFANTASIA_SOURCE',
            'source_table' => 'TABFANTASIA',
            'dest_table' => 'tabfant',
            'source_pk' => 'CDFANTASIA',
            'dest_pk' => 'id',
            'column_map' => [
                'id' => 'CDFANTASIA',
                'CDPROJETO' => 'CDFANTASIA',
                'NOMEPROJETO' => 'DEFANTASIA',
                'LOCAL' => 'NMCONTATO',
                'UF' => 'UFPROJ',
            ],
        ],
        'funcionarios' => [
            'source_env' => 'FUNCIONARIOS_SOURCE',
            'source_table' => 'funcionarios',
            'dest_table' => 'funcionarios',
            'source_pk' => 'matricula',
            'dest_pk' => 'CDMATRFUNCIONARIO',
            'column_map' => [
                'CDMATRFUNCIONARIO' => 'matricula',
                'NMFUNCIONARIO' => 'nome',
                'DTADMISSAO' => 'dtadmissao',
                'CDCARGO' => 'cargo',
                'UFPROJ' => 'estado',
            ],
        ],
    ];

    public function handle(): int
    {
        $target = strtolower((string) $this->argument('table'));
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $targets = $this->resolveTargets($target);
        if (empty($targets)) {
            $this->error("Invalid table. Use: tabfant, funcionarios, or all.");
            return self::INVALID;
        }

        foreach ($targets as $tableKey) {
            $this->line('');
            $this->info("Syncing: {$tableKey}");
            $ok = $this->syncTable($tableKey, $chunkSize, $dryRun);
            if (!$ok) {
                $this->error("Sync failed: {$tableKey}");
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function resolveTargets(string $target): array
    {
        if ($target === '' || $target === 'all') {
            return array_keys(self::TABLES);
        }

        if ($target === 'tabfantasia') {
            return ['tabfant'];
        }

        if (array_key_exists($target, self::TABLES)) {
            return [$target];
        }

        return [];
    }

    private function syncTable(string $tableKey, int $chunkSize, bool $dryRun): bool
    {
        $config = self::TABLES[$tableKey];
        $sourceConfig = $this->readSourceConfig($config['source_env']);

        $missing = $this->missingSourceConfig($sourceConfig);
        if (!empty($missing)) {
            $this->error('Missing source env vars: ' . implode(', ', $missing));
            return false;
        }

        try {
            $pdo = $this->createPdo($sourceConfig);
        } catch (PDOException $e) {
            $this->error('Source connection error: ' . $e->getMessage());
            return false;
        }

        $sourceTable = $config['source_table'];
        $destTable = $config['dest_table'];

        $sourceColumns = $this->fetchSourceColumns($pdo, $sourceTable);
        if (empty($sourceColumns)) {
            $this->error("No columns found in source table: {$sourceTable}");
            return false;
        }

        if (!Schema::hasTable($destTable)) {
            $this->error("Destination table not found: {$destTable}");
            return false;
        }

        $destColumns = Schema::getColumnListing($destTable);
        if (empty($destColumns)) {
            $this->error("No columns found in destination table: {$destTable}");
            return false;
        }

        $destLowerMap = $this->lowerMap($destColumns);
        $sourceLowerMap = $this->lowerMap($sourceColumns);
        $columnMap = $this->normalizeMap($config['column_map']);

        $insertColumns = $this->resolveInsertColumns($destColumns, $sourceLowerMap, $columnMap);
        if (empty($insertColumns)) {
            $this->error('No columns to insert after mapping.');
            return false;
        }

        $destPk = $this->resolveDestPrimaryKey($config, $destLowerMap, $columnMap, $sourceLowerMap);
        if (!in_array($destPk, $insertColumns, true)) {
            $insertColumns[] = $destPk;
        }

        $this->reportColumnInfo($destColumns, $sourceLowerMap, $insertColumns, $columnMap);

        $sql = sprintf(
            'SELECT * FROM %s ORDER BY %s',
            $this->escapeIdentifier($sourceTable),
            $this->escapeIdentifier($config['source_pk'])
        );

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $batch = [];
        $read = 0;
        $inserted = 0;
        $skipped = 0;
        $alreadyExists = 0;
        $now = now();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $read++;
            $mapped = $this->mapRow($row, $insertColumns, $columnMap, $now);

            if (!array_key_exists($destPk, $mapped) || $mapped[$destPk] === null || $mapped[$destPk] === '') {
                $skipped++;
                continue;
            }

            $batch[] = $mapped;
            if (count($batch) >= $chunkSize) {
                [$batchInserted, $batchExists] = $this->flushBatch($destTable, $destPk, $batch, $dryRun);
                $inserted += $batchInserted;
                $alreadyExists += $batchExists;
                $batch = [];
            }
        }

        if (!empty($batch)) {
            [$batchInserted, $batchExists] = $this->flushBatch($destTable, $destPk, $batch, $dryRun);
            $inserted += $batchInserted;
            $alreadyExists += $batchExists;
        }

        $ignored = max(0, $read - $inserted - $skipped - $alreadyExists);

        $this->line('');
        $this->line("Rows read: {$read}");
        if ($dryRun) {
            $this->line("Would insert: {$inserted}");
        } else {
            $this->line("Inserted: {$inserted}");
        }
        $this->line("Skipped (missing PK): {$skipped}");
        $this->line("Already exists: {$alreadyExists}");
        $this->line("Ignored (duplicates/other): {$ignored}");
        $this->line($dryRun ? 'Dry-run: no inserts executed.' : 'Sync complete.');

        return true;
    }

    private function readSourceConfig(string $prefix): array
    {
        $prefix = strtoupper($prefix);

        return [
            'host' => env("{$prefix}_HOST"),
            'port' => env("{$prefix}_PORT", '3306'),
            'database' => env("{$prefix}_DB"),
            'username' => env("{$prefix}_USER"),
            'password' => env("{$prefix}_PASS"),
        ];
    }

    private function missingSourceConfig(array $config): array
    {
        $missing = [];
        foreach (['host', 'database', 'username', 'password'] as $key) {
            if ($config[$key] === null || $config[$key] === '') {
                $missing[] = strtoupper($key);
            }
        }

        return $missing;
    }

    private function createPdo(array $config): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        ];

        if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
            $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );

        return new PDO($dsn, $config['username'], $config['password'], $options);
    }

    private function fetchSourceColumns(PDO $pdo, string $table): array
    {
        $sql = sprintf('SHOW COLUMNS FROM %s', $this->escapeIdentifier($table));
        $stmt = $pdo->query($sql);
        $columns = [];

        foreach ($stmt as $row) {
            if (isset($row['Field'])) {
                $columns[] = (string) $row['Field'];
            }
        }

        return $columns;
    }

    private function lowerMap(array $columns): array
    {
        $map = [];
        foreach ($columns as $column) {
            $map[strtolower((string) $column)] = (string) $column;
        }

        return $map;
    }

    private function normalizeMap(array $map): array
    {
        $normalized = [];
        foreach ($map as $dest => $source) {
            $normalized[strtolower((string) $dest)] = strtolower((string) $source);
        }

        return $normalized;
    }

    private function resolveInsertColumns(array $destColumns, array $sourceLowerMap, array $columnMap): array
    {
        $insertColumns = [];

        foreach ($destColumns as $destColumn) {
            $destLower = strtolower((string) $destColumn);

            if (isset($columnMap[$destLower])) {
                $sourceLower = $columnMap[$destLower];
                if (array_key_exists($sourceLower, $sourceLowerMap)) {
                    $insertColumns[] = $destColumn;
                }
                continue;
            }

            if (array_key_exists($destLower, $sourceLowerMap)) {
                $insertColumns[] = $destColumn;
                continue;
            }

            if ($destLower === 'created_at' || $destLower === 'updated_at') {
                $insertColumns[] = $destColumn;
            }
        }

        return $insertColumns;
    }

    private function resolveDestPrimaryKey(array $config, array $destLowerMap, array $columnMap, array $sourceLowerMap): string
    {
        $destPkLower = strtolower((string) ($config['dest_pk'] ?? $config['source_pk']));
        if (isset($destLowerMap[$destPkLower])) {
            return $destLowerMap[$destPkLower];
        }

        $sourcePkLower = strtolower((string) $config['source_pk']);
        if (isset($destLowerMap[$sourcePkLower])) {
            return $destLowerMap[$sourcePkLower];
        }

        foreach ($columnMap as $destLower => $sourceLower) {
            if ($sourceLower === $sourcePkLower && isset($destLowerMap[$destLower])) {
                return $destLowerMap[$destLower];
            }
        }

        return reset($destLowerMap) ?: $config['source_pk'];
    }

    private function reportColumnInfo(array $destColumns, array $sourceLowerMap, array $insertColumns, array $columnMap): void
    {
        $destLower = array_map('strtolower', $destColumns);
        $insertLower = array_map('strtolower', $insertColumns);

        $usedSource = [];
        foreach ($insertColumns as $destColumn) {
            $destLowerCol = strtolower((string) $destColumn);
            if (isset($columnMap[$destLowerCol])) {
                $usedSource[$columnMap[$destLowerCol]] = true;
            } elseif (isset($sourceLowerMap[$destLowerCol])) {
                $usedSource[$destLowerCol] = true;
            }
        }

        $missingInSource = [];
        foreach ($destLower as $destLowerCol) {
            if (in_array($destLowerCol, $insertLower, true)) {
                continue;
            }
            if ($destLowerCol === 'created_at' || $destLowerCol === 'updated_at') {
                continue;
            }
            $missingInSource[] = $destLowerCol;
        }

        $extraInSource = array_diff(array_keys($sourceLowerMap), array_keys($usedSource));

        $this->line('Columns:');
        $this->line('  Destination: ' . count($destColumns));
        $this->line('  Source: ' . count($sourceLowerMap));
        $this->line('  Insert: ' . count($insertColumns));

        if (!empty($missingInSource)) {
            $preview = implode(', ', array_slice($missingInSource, 0, 10));
            $suffix = count($missingInSource) > 10 ? '...' : '';
            $this->line('  Missing in source (will use default/null): ' . $preview . $suffix);
        }

        if (!empty($extraInSource)) {
            $preview = implode(', ', array_slice($extraInSource, 0, 10));
            $suffix = count($extraInSource) > 10 ? '...' : '';
            $this->line('  Extra in source (ignored): ' . $preview . $suffix);
        }
    }

    private function mapRow(array $sourceRow, array $insertColumns, array $columnMap, $now): array
    {
        $sourceLower = array_change_key_case($sourceRow, CASE_LOWER);
        $mapped = [];

        foreach ($insertColumns as $destColumn) {
            $destLower = strtolower((string) $destColumn);

            if (isset($columnMap[$destLower])) {
                $sourceKey = $columnMap[$destLower];
                $value = $sourceLower[$sourceKey] ?? null;
            } elseif (array_key_exists($destLower, $sourceLower)) {
                $value = $sourceLower[$destLower];
            } elseif ($destLower === 'created_at' || $destLower === 'updated_at') {
                $value = $now;
            } else {
                $value = null;
            }

            if ($destLower === 'id' && $value !== null && $value !== '' && is_numeric($value)) {
                $value = (int) $value;
            }

            $mapped[$destColumn] = $value;
        }

        return $mapped;
    }

    private function flushBatch(string $destTable, string $destPk, array $batch, bool $dryRun): array
    {
        if ($dryRun || empty($batch)) {
            return $this->simulateBatch($destTable, $destPk, $batch);
        }

        $inserted = (int) DB::table($destTable)->insertOrIgnore($batch);
        $alreadyExists = max(0, count($batch) - $inserted);

        return [$inserted, $alreadyExists];
    }

    private function simulateBatch(string $destTable, string $destPk, array $batch): array
    {
        if (empty($batch)) {
            return [0, 0];
        }

        $pkValues = [];
        foreach ($batch as $row) {
            if (array_key_exists($destPk, $row) && $row[$destPk] !== null && $row[$destPk] !== '') {
                $pkValues[] = $row[$destPk];
            }
        }

        $pkValues = array_values(array_unique($pkValues));

        if (empty($pkValues)) {
            return [0, 0];
        }

        $existing = DB::table($destTable)
            ->whereIn($destPk, $pkValues)
            ->pluck($destPk)
            ->all();

        $existingSet = array_flip(array_map('strval', $existing));

        $wouldInsert = 0;
        $alreadyExists = 0;

        foreach ($batch as $row) {
            $pk = $row[$destPk] ?? null;
            if ($pk === null || $pk === '') {
                continue;
            }
            $key = (string) $pk;
            if (isset($existingSet[$key])) {
                $alreadyExists++;
            } else {
                $wouldInsert++;
            }
        }

        return [$wouldInsert, $alreadyExists];
    }

    private function escapeIdentifier(string $name): string
    {
        $escaped = str_replace('`', '``', $name);
        return "`{$escaped}`";
    }
}
