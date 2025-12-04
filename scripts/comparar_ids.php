<?php
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');
$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');

echo "Comparando primeiros 50 patrimônios...\n\n";

$kinghost_first = $kinghost->query('SELECT NUPATRIMONIO FROM patr ORDER BY CAST(NUPATRIMONIO AS UNSIGNED) ASC LIMIT 50')->fetchAll(PDO::FETCH_COLUMN);
$local_first = $local->query('SELECT NUPATRIMONIO FROM patr ORDER BY CAST(NUPATRIMONIO AS UNSIGNED) ASC LIMIT 50')->fetchAll(PDO::FETCH_COLUMN);

echo "LOCAL - 50 primeiros:\n";
print_r(array_slice($local_first, 0, 20));

echo "\n\nKINGHOST - 50 primeiros:\n";
print_r(array_slice($kinghost_first, 0, 20));

echo "\n\nDiferença entre os arrays:\n";
$diff_in_kinghost = array_diff($kinghost_first, $local_first);
$diff_in_local = array_diff($local_first, $kinghost_first);

echo "Extras no KINGHOST: ";
print_r($diff_in_kinghost);

echo "\nFaltam no KINGHOST: ";
print_r($diff_in_local);

// Buscar todos os extras usando subquery
echo "\n\nBuscando ALL extras no KINGHOST via subquery...\n";
$extras_sql = <<<SQL
SELECT NUPATRIMONIO 
FROM patr 
WHERE NUPATRIMONIO NOT IN (SELECT NUPATRIMONIO FROM patr GROUP BY NUPATRIMONIO)
ORDER BY CAST(NUPATRIMONIO AS UNSIGNED) ASC
SQL;

$extras = $kinghost->query($extras_sql)->fetchAll(PDO::FETCH_COLUMN);
echo "Extras encontrados: " . count($extras) . "\n";
if ($extras) {
    print_r(array_slice($extras, 0, 10));
}
