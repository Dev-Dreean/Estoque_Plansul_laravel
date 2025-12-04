<?php
/**
 * LIMPEZA FINAL - REMOVER PATRIMÔNIOS EXTRAS NO KINGHOST
 * 
 * Remove patrimônios que existem no KINGHOST mas NÃO existem no LOCAL
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  LIMPEZA FINAL - REMOVER PATRIMÔNIOS EXTRAS                ║\n";
echo "║  " . date('d/m/Y H:i:s') . "                                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Conexão LOCAL
try {
    $local = new PDO(
        'mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Conectado LOCAL\n";
} catch (PDOException $e) {
    die("❌ Erro LOCAL: " . $e->getMessage() . "\n");
}

// Conexão KINGHOST
try {
    $kinghost = new PDO(
        'mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4',
        'plansul004_add2',
        'A33673170a',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Conectado KINGHOST\n\n";
} catch (PDOException $e) {
    die("❌ Erro KINGHOST: " . $e->getMessage() . "\n");
}

// Buscar IDs de patrimônios no LOCAL
$ids_local = $local->query("SELECT GROUP_CONCAT(NUPATRIMONIO) as ids FROM patr")->fetch(PDO::FETCH_ASSOC)['ids'];
$ids_local_array = explode(',', $ids_local);

// Buscar IDs de patrimônios no KINGHOST
$ids_kinghost_stmt = $kinghost->query("SELECT GROUP_CONCAT(NUPATRIMONIO) as ids FROM patr");
$ids_kinghost = $ids_kinghost_stmt->fetch(PDO::FETCH_ASSOC)['ids'];
$ids_kinghost_array = explode(',', $ids_kinghost);

// Encontrar IDs que estão no KINGHOST mas não estão no LOCAL
$ids_extras = array_diff($ids_kinghost_array, $ids_local_array);

echo "📊 Patrimônios no LOCAL: " . count($ids_local_array) . "\n";
echo "📊 Patrimônios no KINGHOST: " . count($ids_kinghost_array) . "\n";
echo "📊 Patrimônios EXTRAS no KINGHOST: " . count($ids_extras) . "\n\n";

if (count($ids_extras) > 0) {
    echo "Patrimônios a deletar:\n";
    foreach (array_slice($ids_extras, 0, 10) as $id) {
        echo "  - $id\n";
    }
    if (count($ids_extras) > 10) {
        echo "  ... e mais " . (count($ids_extras) - 10) . "\n";
    }
    
    // DELETAR
    echo "\n⚠️  DELETANDO " . count($ids_extras) . " patrimônios extras...\n";
    
    $placeholders = implode(',', array_fill(0, count($ids_extras), '?'));
    $sql = "DELETE FROM patr WHERE NUPATRIMONIO IN ($placeholders)";
    $stmt = $kinghost->prepare($sql);
    $stmt->execute($ids_extras);
    
    echo "✅ Deletados com sucesso!\n\n";
} else {
    echo "✅ Nenhum patrimônio extra encontrado!\n\n";
}

// Verificar resultado
$count_kinghost_final = $kinghost->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "═══════════════════════════════════════════════════════════\n";
echo "RESULTADO FINAL:\n";
echo "LOCAL:    " . count($ids_local_array) . " patrimônios\n";
echo "KINGHOST: $count_kinghost_final patrimônios\n";

if ($count_kinghost_final == count($ids_local_array)) {
    echo "\n✅ CONTAGENS IGUAIS - LIMPEZA CONCLUÍDA!\n";
} else {
    echo "\n⚠️  Ainda há diferença de " . ($count_kinghost_final - count($ids_local_array)) . " registros\n";
}
