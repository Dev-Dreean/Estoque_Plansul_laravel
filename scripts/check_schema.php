<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');

echo "Colunas tabfant:\n";
$cols = $db->query('DESCRIBE tabfant')->fetchAll();
foreach ($cols as $c) {
    echo "  " . $c['Field'] . "\n";
}

echo "\nColunas locais_projeto:\n";
$cols = $db->query('DESCRIBE locais_projeto')->fetchAll();
foreach ($cols as $c) {
    echo "  " . $c['Field'] . "\n";
}

echo "\nColunas patr:\n";
$cols = $db->query('DESCRIBE patr')->fetchAll();
foreach ($cols as $c) {
    echo "  " . $c['Field'] . "\n";
}

echo "\nPrimeiras 2 linhas patr:\n";
$rows = $db->query('SELECT * FROM patr LIMIT 2')->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
