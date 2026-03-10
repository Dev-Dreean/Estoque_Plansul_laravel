<?php

declare(strict_types=1);

function checkConnection(string $label, string $dsn, string $user, string $pass): int
{
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $row = $pdo->query('SELECT DATABASE() AS db')->fetch();
        echo $label . '_OK: ' . ($row['db'] ?? '-') . PHP_EOL;
        return 0;
    } catch (Throwable $e) {
        echo $label . '_ERR: ' . $e->getMessage() . PHP_EOL;
        return 1;
    }
}

$local = checkConnection(
    'LOCAL',
    'mysql:host=127.0.0.1;port=3306;dbname=cadastros_plansul;charset=utf8mb4',
    'root',
    ''
);

$remote = checkConnection(
    'REMOTE',
    'mysql:host=mysql07-farm10.kinghost.net;port=3306;dbname=plansul04;charset=utf8mb4',
    'plansul004_add2',
    'A33673170a'
);

exit($local || $remote ? 1 : 0);
