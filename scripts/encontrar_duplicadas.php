<?php
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');
$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');

// Buscar hash de TODOS os patrimônios
$kinghost_hashes = $kinghost->query('SELECT NUPATRIMONIO, MD5(CONCAT(NUPATRIMONIO, DEPATRIMONIO, USUARIO, CDPROJETO)) AS hash FROM patr ORDER BY NUPATRIMONIO')->fetchAll(PDO::FETCH_KEY_PAIR);
$local_hashes = $local->query('SELECT NUPATRIMONIO, MD5(CONCAT(NUPATRIMONIO, DEPATRIMONIO, USUARIO, CDPROJETO)) AS hash FROM patr ORDER BY NUPATRIMONIO')->fetchAll(PDO::FETCH_KEY_PAIR);

echo "LOCAL: " . count($local_hashes) . " patrimônios\n";
echo "KINGHOST: " . count($kinghost_hashes) . " patrimônios\n\n";

// IDs que estão duplicados no KINGHOST
$value_counts = array_count_values($kinghost_hashes);
$duplicates = array_filter($value_counts, function($v) { return $v > 1; });

if ($duplicates) {
    echo "Hashes duplicados no KINGHOST:\n";
    $duplicate_hashes = array_keys($duplicates);
    $result = $kinghost->query('SELECT NUPATRIMONIO, DEPATRIMONIO, USUARIO FROM patr WHERE MD5(CONCAT(NUPATRIMONIO, DEPATRIMONIO, USUARIO, CDPROJETO)) IN (' . implode(',', array_map(function($v) { return '"' . $v . '"'; }, $duplicate_hashes)) . ') ORDER BY NUPATRIMONIO');
    
    foreach ($result as $row) {
        echo "  ID {$row['NUPATRIMONIO']}: {$row['DEPATRIMONIO']} ({$row['USUARIO']})\n";
    }
}

// Patrimônios no KINGHOST que NÃO estão no LOCAL  
$kg_ids = array_keys($kinghost_hashes);
$lc_ids = array_keys($local_hashes);

$extras_ids = array_diff($kg_ids, $lc_ids);
echo "\n\nIDs que existem em KINGHOST mas NÃO em LOCAL: " . count($extras_ids) . "\n";
if ($extras_ids) {
    foreach (array_slice($extras_ids, 0, 10) as $id) {
        echo "  $id\n";
    }
}
