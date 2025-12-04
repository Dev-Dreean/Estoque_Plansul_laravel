<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');

echo "Colunas movpartr:\n";
$cols = $db->query('DESCRIBE movpartr')->fetchAll();
foreach ($cols as $c) {
    echo "  " . $c['Field'] . "\n";
}

echo "\nPrimeiras 2 linhas:\n";
$rows = $db->query('SELECT * FROM movpartr LIMIT 2')->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
