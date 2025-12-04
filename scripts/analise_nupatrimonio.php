<?php
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');

// Contar por padrão
echo "Análise de NUPATRIMONIO no KINGHOST:\n\n";

$stmt = $kinghost->query('SELECT NUPATRIMONIO, LENGTH(NUPATRIMONIO) AS tam, HEX(NUPATRIMONIO) AS hex FROM patr LIMIT 20');
foreach ($stmt as $row) {
    echo "ID: {$row['NUPATRIMONIO']} | Tamanho: {$row['tam']} | HEX: {$row['hex']}\n";
}

echo "\n\nVerificando valores nulos/vazios:\n";
$null_count = $kinghost->query('SELECT COUNT(*) FROM patr WHERE NUPATRIMONIO IS NULL OR NUPATRIMONIO = ""')->fetch()[0];
echo "NULLs/vazios: $null_count\n";

// Buscar últimos 10
echo "\n\nÚltimos 10 patrimônios:\n";
$stmt = $kinghost->query('SELECT NUPATRIMONIO, DEPATRIMONIO FROM patr ORDER BY NUPATRIMONIO DESC LIMIT 10');
foreach ($stmt as $row) {
    echo "  {$row['NUPATRIMONIO']}: {$row['DEPATRIMONIO']}\n";
}

// Buscar primeiro 10
echo "\n\nPrimeiros 10 patrimônios:\n";
$stmt = $kinghost->query('SELECT NUPATRIMONIO, DEPATRIMONIO FROM patr ORDER BY NUPATRIMONIO ASC LIMIT 10');
foreach ($stmt as $row) {
    echo "  {$row['NUPATRIMONIO']}: {$row['DEPATRIMONIO']}\n";
}
