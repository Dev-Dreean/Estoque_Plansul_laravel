<?php
/**
 * LIMPEZA FINAL v2 - REMOVER PATRIMÔNIOS EXTRAS
 * Usando fetchAll em vez de GROUP_CONCAT
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Conectando...\n";
$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');

// Buscar IDs usando fetchAll
$local_patr = $local->query('SELECT NUPATRIMONIO FROM patr ORDER BY NUPATRIMONIO')->fetchAll(PDO::FETCH_COLUMN);
$kinghost_patr = $kinghost->query('SELECT NUPATRIMONIO FROM patr ORDER BY NUPATRIMONIO')->fetchAll(PDO::FETCH_COLUMN);

echo "LOCAL: " . count($local_patr) . " patrimônios\n";
echo "KINGHOST: " . count($kinghost_patr) . " patrimônios\n";

// Encontrar extras
$extras = array_diff($kinghost_patr, $local_patr);
echo "EXTRAS NO KINGHOST: " . count($extras) . "\n\n";

if (count($extras) > 0) {
    echo "Primeiro 20 extras:\n";
    foreach (array_slice($extras, 0, 20) as $id) {
        echo "  - $id\n";
    }
    
    // DELETAR
    echo "\n⚠️  Deletando " . count($extras) . " patrimônios extras...\n";
    
    foreach (array_chunk($extras, 100) as $chunk) {
        $placeholders = implode(',', $chunk);
        $sql = "DELETE FROM patr WHERE NUPATRIMONIO IN ($placeholders)";
        $kinghost->exec($sql);
        echo "✅ Deletados " . count($chunk) . "\n";
    }
    
    $final_count = $kinghost->query('SELECT COUNT(*) FROM patr')->fetch()[0];
    echo "\nRESULTADO: $final_count patrimônios no KINGHOST\n";
    
    if ($final_count == count($local_patr)) {
        echo "🎉 SUCESSO - Contagens iguais!\n";
    }
} else {
    echo "✅ Sem extras para deletar\n";
}
