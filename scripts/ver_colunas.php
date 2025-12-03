<?php

$pdo = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04', 'plansul004_add2', 'A33673170a');

echo "Colunas da tabela 'patr':\n\n";
$stmt = $pdo->query('SHOW COLUMNS FROM patr');
while ($row = $stmt->fetch()) {
    echo $row['Field'] . "\n";
}
