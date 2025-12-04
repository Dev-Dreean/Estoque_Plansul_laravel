<?php
/**
 * SINCRONIZAR TABFANT (Projetos) E LOCAIS_PROJETO
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');

echo "=== SINCRONIZANDO TABFANT (Projetos) ===\n\n";

// Contar inicial
$local_proj = $local->query('SELECT COUNT(*) FROM tabfant')->fetch()[0];
$kinghost_proj = $kinghost->query('SELECT COUNT(*) FROM tabfant')->fetch()[0];

echo "Antes: LOCAL=$local_proj | KINGHOST=$kinghost_proj\n";

// Sincronizar projetos
$projetos = $local->query('SELECT * FROM tabfant')->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$inserted = 0;
$errors = 0;

foreach ($projetos as $proj) {
    try {
        $cdprojeto = $proj['CDPROJETO'];
        
        // Verificar se existe
        $exists = $kinghost->query("SELECT 1 FROM tabfant WHERE CDPROJETO = " . (int)$cdprojeto)->fetch();
        
        if ($exists) {
            // UPDATE
            $fields = array_keys($proj);
            $set = implode(', ', array_map(function($f) { return "`$f` = ?"; }, $fields));
            $sql = "UPDATE tabfant SET $set WHERE CDPROJETO = ?";
            $values = array_values($proj);
            $values[] = $cdprojeto;
            
            $stmt = $kinghost->prepare($sql);
            $stmt->execute($values);
            $updated++;
        } else {
            // INSERT
            $fields = array_keys($proj);
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $sql = "INSERT INTO tabfant (" . implode(',', array_map(function($f) { return "`$f`"; }, $fields)) . ") VALUES ($placeholders)";
            
            $stmt = $kinghost->prepare($sql);
            $stmt->execute(array_values($proj));
            $inserted++;
        }
    } catch (Exception $e) {
        echo "❌ Erro em CDPROJETO=$cdprojeto: {$e->getMessage()}\n";
        $errors++;
    }
}

$kinghost_proj_final = $kinghost->query('SELECT COUNT(*) FROM tabfant')->fetch()[0];
echo "✅ Projetos: Atualizados=$updated, Inseridos=$inserted, Erros=$errors\n";
echo "Depois: KINGHOST=$kinghost_proj_final (esperado $local_proj)\n\n";

// ============================================

echo "=== SINCRONIZANDO LOCAIS_PROJETO (Locais) ===\n\n";

$local_locais = $local->query('SELECT COUNT(*) FROM locais_projeto')->fetch()[0];
$kinghost_locais = $kinghost->query('SELECT COUNT(*) FROM locais_projeto')->fetch()[0];

echo "Antes: LOCAL=$local_locais | KINGHOST=$kinghost_locais\n";

$locais = $local->query('SELECT * FROM locais_projeto')->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$inserted = 0;
$errors = 0;

foreach ($locais as $local_obj) {
    try {
        $cdlocal = $local_obj['CDLOCAL'];
        
        $exists = $kinghost->query("SELECT 1 FROM locais_projeto WHERE CDLOCAL = " . (int)$cdlocal)->fetch();
        
        if ($exists) {
            $fields = array_keys($local_obj);
            $set = implode(', ', array_map(function($f) { return "`$f` = ?"; }, $fields));
            $sql = "UPDATE locais_projeto SET $set WHERE CDLOCAL = ?";
            $values = array_values($local_obj);
            $values[] = $cdlocal;
            
            $stmt = $kinghost->prepare($sql);
            $stmt->execute($values);
            $updated++;
        } else {
            $fields = array_keys($local_obj);
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $sql = "INSERT INTO locais_projeto (" . implode(',', array_map(function($f) { return "`$f`"; }, $fields)) . ") VALUES ($placeholders)";
            
            $stmt = $kinghost->prepare($sql);
            $stmt->execute(array_values($local_obj));
            $inserted++;
        }
    } catch (Exception $e) {
        echo "❌ Erro em CDLOCAL=$cdlocal: {$e->getMessage()}\n";
        $errors++;
    }
}

$kinghost_locais_final = $kinghost->query('SELECT COUNT(*) FROM locais_projeto')->fetch()[0];
echo "✅ Locais: Atualizados=$updated, Inseridos=$inserted, Erros=$errors\n";
echo "Depois: KINGHOST=$kinghost_locais_final (esperado $local_locais)\n\n";

// ============================================

echo "=== RESUMO FINAL ===\n";
echo "PROJETOS:   LOCAL=$local_proj → KINGHOST=$kinghost_proj_final (" . ($local_proj == $kinghost_proj_final ? "✅ OK" : "⚠️  DIFERENÇA") . ")\n";
echo "LOCAIS:     LOCAL=$local_locais → KINGHOST=$kinghost_locais_final (" . ($local_locais == $kinghost_locais_final ? "✅ OK" : "⚠️  DIFERENÇA") . ")\n";
