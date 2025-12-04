<?php
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');
$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');

echo "=== LIMPEZA FINAL - DELETAR E SINCRONIZAR ===\n\n";

// DELETAR tudo
echo "Deletando dados atuais...\n";
$kinghost->exec('DELETE FROM movpartr');
$kinghost->exec('DELETE FROM patr');

// Inserir patrimônios
echo "Inserindo patrimônios...\n";
$patr = $local->query('SELECT * FROM patr')->fetchAll(PDO::FETCH_ASSOC);

$inserted = 0;
foreach ($patr as $p) {
    try {
        $fields = array_keys($p);
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO patr (" . implode(',', $fields) . ") VALUES ($placeholders)";
        
        $stmt = $kinghost->prepare($sql);
        $stmt->execute(array_values($p));
        $inserted++;
    } catch (Exception $e) {
        echo "❌ Erro: {$e->getMessage()}\n";
    }
}

$p_total = $kinghost->query('SELECT COUNT(*) FROM patr')->fetch()[0];
echo "✅ $inserted patrimônios inseridos → Total: $p_total\n\n";

// Inserir histórico
echo "Inserindo histórico...\n";
$mov = $local->query('SELECT * FROM movpartr')->fetchAll(PDO::FETCH_ASSOC);

$inserted = 0;
foreach ($mov as $m) {
    try {
        $fields = array_keys($m);
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO movpartr (" . implode(',', $fields) . ") VALUES ($placeholders)";
        
        $stmt = $kinghost->prepare($sql);
        $stmt->execute(array_values($m));
        $inserted++;
    } catch (Exception $e) {
        echo "❌ Erro: {$e->getMessage()}\n";
    }
}

$m_total = $kinghost->query('SELECT COUNT(*) FROM movpartr')->fetch()[0];
echo "✅ $inserted históricos inseridos → Total: $m_total\n\n";

echo "=== RESULTADO ===\n";
echo "Patrimônios: $p_total (LOCAL: " . $local->query('SELECT COUNT(*) FROM patr')->fetch()[0] . ")\n";
echo "Histórico:   $m_total (LOCAL: " . $local->query('SELECT COUNT(*) FROM movpartr')->fetch()[0] . ")\n";
