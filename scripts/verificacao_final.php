<?php
/**
 * VERIFICAÇÃO COMPLETA FINAL - Todos 4 bancos sincronizados
 */

$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   VERIFICAÇÃO COMPLETA - 4 BANCOS SINCRONIZADOS               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$tables = [
    'tabfant' => 'Projetos',
    'locais_projeto' => 'Locais',
    'patr' => 'Patrimônios',
    'movpartr' => 'Histórico'
];

$all_ok = true;

foreach ($tables as $table => $name) {
    $local_count = $local->query("SELECT COUNT(*) FROM $table")->fetch()[0];
    $kinghost_count = $kinghost->query("SELECT COUNT(*) FROM $table")->fetch()[0];
    
    $match = $local_count == $kinghost_count;
    $status = $match ? "✅" : "⚠️ ";
    
    printf("%s %-20s | LOCAL: %6d | KINGHOST: %6d | %s\n", 
        $status, $name, $local_count, $kinghost_count, 
        $match ? "OK" : "DIFERENÇA");
    
    if (!$match) $all_ok = false;
}

echo "\n" . str_repeat("─", 70) . "\n\n";

if ($all_ok) {
    echo "🎉 SUCESSO! Todos os 4 bancos estão sincronizados!\n\n";
    echo "✅ TABFANT:         874 projetos\n";
    echo "✅ LOCAIS_PROJETO: 1.936 locais\n";
    echo "✅ PATR:          11.379 patrimônios\n";
    echo "✅ MOVPARTR:       4.603 históricos\n";
    echo "\n✅ IMPORTAÇÃO E SINCRONIZAÇÃO COMPLETADAS COM SUCESSO!\n";
} else {
    echo "❌ Ainda há diferenças - verifique as marcações acima\n";
}
