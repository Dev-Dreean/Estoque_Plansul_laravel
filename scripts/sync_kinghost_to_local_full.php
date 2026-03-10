<?php

declare(strict_types=1);

const LOCAL_HOST = '127.0.0.1';
const LOCAL_PORT = '3306';
const LOCAL_DB = 'cadastros_plansul';
const LOCAL_USER = 'root';
const LOCAL_PASS = '';

const REMOTE_HOST = 'mysql07-farm10.kinghost.net';
const REMOTE_PORT = '3306';
const REMOTE_DB = 'plansul04';
const REMOTE_USER = 'plansul004_add2';
const REMOTE_PASS = 'A33673170a';

const BATCH_SIZE = 250;

function connectServer(string $host, string $port, string $user, string $pass, ?string $db = null): PDO
{
    $dsn = 'mysql:host=' . $host . ';port=' . $port;
    if ($db !== null) {
        $dsn .= ';dbname=' . $db;
    }
    $dsn .= ';charset=utf8mb4';

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    ]);
}

function quoteName(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function line(string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

function getObjects(PDO $pdo, string $db, string $type): array
{
    $stmt = $pdo->prepare(
        'SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ? AND TABLE_TYPE = ? ORDER BY TABLE_NAME'
    );
    $stmt->execute([$db, $type]);
    return array_map(static fn (array $row) => (string) $row['TABLE_NAME'], $stmt->fetchAll());
}

function getCreateTable(PDO $pdo, string $table): string
{
    $row = $pdo->query('SHOW CREATE TABLE ' . quoteName($table))->fetch();
    return (string) ($row['Create Table'] ?? '');
}

function getCreateView(PDO $pdo, string $view): string
{
    $row = $pdo->query('SHOW CREATE VIEW ' . quoteName($view))->fetch();
    return (string) ($row['Create View'] ?? '');
}

function stripDefiner(string $sql): string
{
    $sql = preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s*/i', '', $sql) ?: $sql;
    $sql = preg_replace('/SQL SECURITY DEFINER/i', 'SQL SECURITY INVOKER', $sql) ?: $sql;
    return $sql;
}

function backupLocalDatabase(PDO $localServer, PDO $localDb, string $backupDb): void
{
    line('Criando backup local: ' . $backupDb);
    $localServer->exec('CREATE DATABASE ' . quoteName($backupDb) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $tables = getObjects($localDb, LOCAL_DB, 'BASE TABLE');
    foreach ($tables as $table) {
        line('Backup tabela: ' . $table);
        $localServer->exec(
            'CREATE TABLE ' . quoteName($backupDb) . '.' . quoteName($table) .
            ' LIKE ' . quoteName(LOCAL_DB) . '.' . quoteName($table)
        );
        $localServer->exec(
            'INSERT INTO ' . quoteName($backupDb) . '.' . quoteName($table) .
            ' SELECT * FROM ' . quoteName(LOCAL_DB) . '.' . quoteName($table)
        );
    }
}

function dropLocalObjects(PDO $localDb): void
{
    $views = getObjects($localDb, LOCAL_DB, 'VIEW');
    $tables = getObjects($localDb, LOCAL_DB, 'BASE TABLE');

    $localDb->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach ($views as $view) {
        line('Removendo view local: ' . $view);
        $localDb->exec('DROP VIEW IF EXISTS ' . quoteName($view));
    }

    foreach ($tables as $table) {
        line('Removendo tabela local: ' . $table);
        $localDb->exec('DROP TABLE IF EXISTS ' . quoteName($table));
    }

    $localDb->exec('SET FOREIGN_KEY_CHECKS=1');
}

function createRemoteTablesLocally(PDO $remoteDb, PDO $localDb): void
{
    $tables = getObjects($remoteDb, REMOTE_DB, 'BASE TABLE');
    $pending = [];

    foreach ($tables as $table) {
        $pending[$table] = getCreateTable($remoteDb, $table);
    }

    $localDb->exec('SET FOREIGN_KEY_CHECKS=0');

    $guard = count($pending) + 5;
    while (!empty($pending) && $guard-- > 0) {
        $progress = 0;
        foreach ($pending as $table => $createSql) {
            try {
                $localDb->exec($createSql);
                line('Tabela criada: ' . $table);
                unset($pending[$table]);
                $progress++;
            } catch (PDOException $e) {
                // tenta de novo na próxima rodada se depender de outra tabela
            }
        }

        if ($progress === 0) {
            break;
        }
    }

    $localDb->exec('SET FOREIGN_KEY_CHECKS=1');

    if (!empty($pending)) {
        throw new RuntimeException('Não foi possível criar todas as tabelas: ' . implode(', ', array_keys($pending)));
    }
}

function buildInsertSql(string $table, array $columns): string
{
    $quotedColumns = array_map('quoteName', $columns);
    $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

    return 'INSERT INTO ' . quoteName($table)
        . ' (' . implode(', ', $quotedColumns) . ') VALUES '
        . $placeholders;
}

function insertBatch(PDO $localDb, string $table, array $rows): void
{
    if (empty($rows)) {
        return;
    }

    $columns = array_keys($rows[0]);
    $singlePlaceholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
    $sql = 'INSERT INTO ' . quoteName($table)
        . ' (' . implode(', ', array_map('quoteName', $columns)) . ') VALUES '
        . implode(', ', array_fill(0, count($rows), $singlePlaceholder));

    $bindings = [];
    foreach ($rows as $row) {
        foreach ($columns as $column) {
            $bindings[] = $row[$column];
        }
    }

    $stmt = $localDb->prepare($sql);
    $stmt->execute($bindings);
}

function copyTableData(PDO $remoteDb, PDO $localDb, string $table): int
{
    $stmt = $remoteDb->query('SELECT * FROM ' . quoteName($table));
    $count = 0;
    $batch = [];

    $localDb->beginTransaction();
    try {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $batch[] = $row;
            $count++;

            if (count($batch) >= BATCH_SIZE) {
                insertBatch($localDb, $table, $batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            insertBatch($localDb, $table, $batch);
        }

        $localDb->commit();
    } catch (Throwable $e) {
        if ($localDb->inTransaction()) {
            $localDb->rollBack();
        }
        throw $e;
    }

    return $count;
}

function createRemoteViewsLocally(PDO $remoteDb, PDO $localDb): void
{
    $views = getObjects($remoteDb, REMOTE_DB, 'VIEW');
    foreach ($views as $view) {
        $createView = stripDefiner(getCreateView($remoteDb, $view));
        if ($createView === '') {
            continue;
        }
        $createView = preg_replace('/^CREATE\s+/i', 'CREATE OR REPLACE ', $createView) ?: $createView;
        $localDb->exec($createView);
        line('View criada: ' . $view);
    }
}

try {
    line('Conectando aos bancos');
    $localServer = connectServer(LOCAL_HOST, LOCAL_PORT, LOCAL_USER, LOCAL_PASS);
    $localDb = connectServer(LOCAL_HOST, LOCAL_PORT, LOCAL_USER, LOCAL_PASS, LOCAL_DB);
    $remoteDb = connectServer(REMOTE_HOST, REMOTE_PORT, REMOTE_USER, REMOTE_PASS, REMOTE_DB);

    $backupDb = LOCAL_DB . '_backup_' . date('Ymd_His');

    backupLocalDatabase($localServer, $localDb, $backupDb);
    dropLocalObjects($localDb);
    createRemoteTablesLocally($remoteDb, $localDb);

    $tables = getObjects($remoteDb, REMOTE_DB, 'BASE TABLE');
    line('Copiando dados de ' . count($tables) . ' tabelas');
    foreach ($tables as $table) {
        $rows = copyTableData($remoteDb, $localDb, $table);
        line('Tabela sincronizada: ' . $table . ' (' . $rows . ' registros)');
    }

    createRemoteViewsLocally($remoteDb, $localDb);

    line('Sincronização concluída com sucesso.');
    line('Backup local salvo em: ' . $backupDb);
    exit(0);
} catch (Throwable $e) {
    line('ERRO: ' . $e->getMessage());
    exit(1);
}
