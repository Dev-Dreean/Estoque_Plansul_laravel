<?php
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');

echo "Removendo 4 patrimônios extras...\n";

// Buscar patrimônios no KINGHOST que não existem no LOCAL
$kinghost_patr = $kinghost->query('SELECT NUPATRIMONIO FROM patr ORDER BY CAST(NUPATRIMONIO AS UNSIGNED)')->fetchAll(PDO::FETCH_COLUMN);

$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');
$local_patr = $local->query('SELECT NUPATRIMONIO FROM patr ORDER BY CAST(NUPATRIMONIO AS UNSIGNED)')->fetchAll(PDO::FETCH_COLUMN);

$extras = array_diff($kinghost_patr, $local_patr);

echo "Patrimônios extras encontrados: " . count($extras) . "\n";

foreach ($extras as $id) {
    echo "  Deletando patrimônio $id\n";
    $kinghost->exec("DELETE FROM patr WHERE NUPATRIMONIO = '$id'");
    $kinghost->exec("DELETE FROM movpartr WHERE NUPATR = '$id'");
}

echo "\nTotais após limpeza:\n";
$patr_final = $kinghost->query('SELECT COUNT(*) FROM patr')->fetch()[0];
$mov_final = $kinghost->query('SELECT COUNT(*) FROM movpartr')->fetch()[0];

echo "Patrimônios: $patr_final\n";
echo "Históricos: $mov_final\n";

// Deletar órfãos restantes
$orphaned = $kinghost->exec('DELETE FROM movpartr WHERE NUPATR NOT IN (SELECT NUPATRIMONIO FROM patr)');
echo "\nOrfãos deletados: $orphaned\n";

$mov_final2 = $kinghost->query('SELECT COUNT(*) FROM movpartr')->fetch()[0];
echo "Históricos finais: $mov_final2\n";
