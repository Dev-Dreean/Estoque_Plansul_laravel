<?php
$pdo = new PDO('mysql:host=localhost;dbname=plansul', 'root', '');
$result = $pdo->query('DESCRIBE usuario');

foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['Field'] . ' | ' . $col['Type'] . ' | ' . $col['Null'] . "\n";
}
