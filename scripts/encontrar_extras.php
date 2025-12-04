<?php
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');
$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');

// Buscar patrimônios no KINGHOST que não existem no LOCAL
$kinghost_ids = $kinghost->query('SELECT NUPATRIMONIO FROM patr ORDER BY NUPATRIMONIO')->fetchAll(PDO::FETCH_COLUMN);
$local_ids = $local->query('SELECT NUPATRIMONIO FROM patr ORDER BY NUPATRIMONIO')->fetchAll(PDO::FETCH_COLUMN);

$extras = array_diff($kinghost_ids, $local_ids);

echo "Patrimônios EXTRAS no KINGHOST:\n";
foreach ($extras as $id) {
    echo "  NUPATRIMONIO: $id\n";
    
    // Buscar detalhes
    $row = $kinghost->query("SELECT NUPATRIMONIO, DEPATRIMONIO, USUARIO FROM patr WHERE NUPATRIMONIO = $id")->fetch();
    if ($row) {
        echo "    DESC: {$row['DEPATRIMONIO']}\n";
        echo "    USER: {$row['USUARIO']}\n";
    }
}

echo "\n\nDeletando os 4 extras...\n";
foreach (array_chunk($extras, 100) as $chunk) {
    $placeholders = implode(',', array_map(function($v) { return "'$v'"; }, $chunk));
    $kinghost->exec("DELETE FROM patr WHERE NUPATRIMONIO IN ($placeholders)");
}

$final = $kinghost->query('SELECT COUNT(*) FROM patr')->fetch()[0];
echo "Resultado final: $final patrimônios\n";
