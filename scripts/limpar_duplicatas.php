<?php
/**
 * LIMPAR DUPLICATAS criadas durante o teste
 */

$pdo = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04', 'plansul004_add2', 'A33673170a');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "🧹 LIMPEZA DE DUPLICATAS\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Ver duplicatas
$check = $pdo->query("
    SELECT NUPATRIMONIO, COUNT(*) as total 
    FROM patr 
    WHERE NUPATRIMONIO IN (3, 38, 45, 100)
    GROUP BY NUPATRIMONIO
    HAVING COUNT(*) > 1
");

$duplicatas = $check->fetchAll(PDO::FETCH_ASSOC);

if (empty($duplicatas)) {
    echo "✅ Nenhuma duplicata encontrada para patrimônios 3, 38, 45, 100\n";
    exit;
}

echo "📊 Duplicatas encontradas:\n";
foreach ($duplicatas as $dup) {
    echo "  Patrimônio #{$dup['NUPATRIMONIO']}: {$dup['total']} registros\n";
}
echo "\n";

// Para cada número de patrimônio duplicado, manter apenas o MAIS ANTIGO (menor NUSEQPATR)
foreach ($duplicatas as $dup) {
    $nupatrimonio = $dup['NUPATRIMONIO'];
    
    echo "🔄 Limpando patrimônio #$nupatrimonio...\n";
    
    // Pegar o ID do registro mais antigo
    $stmt = $pdo->prepare("
        SELECT NUSEQPATR, SITUACAO, USUARIO, CDPROJETO 
        FROM patr 
        WHERE NUPATRIMONIO = ? 
        ORDER BY NUSEQPATR ASC
    ");
    $stmt->execute([$nupatrimonio]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  Registros encontrados:\n";
    foreach ($registros as $idx => $reg) {
        echo sprintf("    [%d] NUSEQPATR=%s | SITUACAO=%s | USUARIO=%s | CDPROJETO=%s\n",
            $idx + 1,
            $reg['NUSEQPATR'],
            $reg['SITUACAO'] ?: '(vazio)',
            $reg['USUARIO'] ?: '(vazio)',
            $reg['CDPROJETO'] ?: '(vazio)'
        );
    }
    
    // Manter o primeiro (mais antigo), deletar os demais
    $keepId = $registros[0]['NUSEQPATR'];
    
    $deleteStmt = $pdo->prepare("DELETE FROM patr WHERE NUPATRIMONIO = ? AND NUSEQPATR != ?");
    $deleteStmt->execute([$nupatrimonio, $keepId]);
    
    $deleted = $deleteStmt->rowCount();
    echo "  ✅ Deletados $deleted registros duplicados, mantido NUSEQPATR=$keepId\n\n";
}

echo "✅ Limpeza concluída!\n";
