<?php
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');
$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');

echo "=== SINCRONIZAÇÃO FINAL E LIMPA ===\n\n";

// 1. DELETAR TUDO em tabfant e locais_projeto no KINGHOST
echo "Deletando dados existentes...\n";
$kinghost->exec('DELETE FROM tabfant');
$kinghost->exec('DELETE FROM locais_projeto');

// 2. INSERIR dados do LOCAL
echo "Inserindo dados do LOCAL...\n\n";

// TABFANT
$projetos = $local->query('SELECT * FROM tabfant')->fetchAll(PDO::FETCH_ASSOC);
$inserted_proj = 0;

foreach ($projetos as $proj) {
    try {
        $fields = array_keys($proj);
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO tabfant (" . implode(',', array_map(function($f) { return "`$f`"; }, $fields)) . ") VALUES ($placeholders)";
        
        $stmt = $kinghost->prepare($sql);
        $stmt->execute(array_values($proj));
        $inserted_proj++;
    } catch (Exception $e) {
        echo "❌ Erro em projeto: {$e->getMessage()}\n";
    }
}

$proj_final = $kinghost->query('SELECT COUNT(*) FROM tabfant')->fetch()[0];
echo "✅ Projetos: $inserted_proj inseridos → Total: $proj_final\n\n";

// LOCAIS_PROJETO
$locais = $local->query('SELECT * FROM locais_projeto')->fetchAll(PDO::FETCH_ASSOC);
$inserted_loc = 0;

foreach ($locais as $loc) {
    try {
        $fields = array_keys($loc);
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO locais_projeto (" . implode(',', array_map(function($f) { return "`$f`"; }, $fields)) . ") VALUES ($placeholders)";
        
        $stmt = $kinghost->prepare($sql);
        $stmt->execute(array_values($loc));
        $inserted_loc++;
    } catch (Exception $e) {
        echo "❌ Erro em local: {$e->getMessage()}\n";
    }
}

$loc_final = $kinghost->query('SELECT COUNT(*) FROM locais_projeto')->fetch()[0];
echo "✅ Locais: $inserted_loc inseridos → Total: $loc_final\n\n";

// COMPARAR
$proj_local = $local->query('SELECT COUNT(*) FROM tabfant')->fetch()[0];
$loc_local = $local->query('SELECT COUNT(*) FROM locais_projeto')->fetch()[0];

echo "=== RESULTADO FINAL ===\n";
echo "TABFANT:\n";
echo "  LOCAL:    $proj_local\n";
echo "  KINGHOST: $proj_final\n";
echo "  Status: " . ($proj_final == $proj_local ? "✅ OK" : "⚠️  DIFERENÇA") . "\n\n";

echo "LOCAIS_PROJETO:\n";
echo "  LOCAL:    $loc_local\n";
echo "  KINGHOST: $loc_final\n";
echo "  Status: " . ($loc_final == $loc_local ? "✅ OK" : "⚠️  DIFERENÇA") . "\n";
