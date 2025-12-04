<?php
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');
$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');

echo "Limpando registros inválidos...\n";

// Deletar NUPATRIMONIO = 0
$result = $kinghost->exec('DELETE FROM patr WHERE NUPATRIMONIO = 0 OR NUPATRIMONIO IS NULL');
echo "Deletados $result registros inválidos\n";

// Contar resultado
$kinghost_count = $kinghost->query('SELECT COUNT(*) FROM patr')->fetch()[0];
$local_count = $local->query('SELECT COUNT(*) FROM patr')->fetch()[0];

echo "\nRESULTADO FINAL:\n";
echo "LOCAL:    $local_count patrimônios\n";
echo "KINGHOST: $kinghost_count patrimônios\n";
echo "Diferença: " . ($kinghost_count - $local_count) . "\n";

if ($kinghost_count == $local_count) {
    echo "✅ PATRIMÔNIOS SINCRONIZADOS COM SUCESSO!\n";
} else {
    echo "⚠️  Ainda há diferença de " . ($kinghost_count - $local_count) . "\n";
}

// Limpar histórico órfão
echo "\n\nLimpando HISTÓRICO órfão...\n";
$deleted = $kinghost->exec('DELETE FROM movpartr WHERE NUPATR NOT IN (SELECT NUPATRIMONIO FROM patr)');
echo "Deletados $deleted históricos órfãos\n";

$movpartr_kinghost = $kinghost->query('SELECT COUNT(*) FROM movpartr')->fetch()[0];
$movpartr_local = $local->query('SELECT COUNT(*) FROM movpartr')->fetch()[0];

echo "\nRESULTADO FINAL HISTÓRICO:\n";
echo "LOCAL:    $movpartr_local históricos\n";
echo "KINGHOST: $movpartr_kinghost históricos\n";
echo "Diferença: " . ($movpartr_kinghost - $movpartr_local) . "\n";

if ($movpartr_kinghost == $movpartr_local) {
    echo "✅ HISTÓRICO SINCRONIZADO COM SUCESSO!\n";
}
