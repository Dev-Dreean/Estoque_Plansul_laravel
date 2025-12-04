<?php
/**
 * VERIFICAÇÃO DE AMOSTRAGEM FINAL - Campos críticos
 */

$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');
$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   VERIFICAÇÃO DE AMOSTRAGEM - CAMPOS CRÍTICOS                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Amostra: patrimônios específicos do histórico anterior
$samples = [5243, 33074, 16216, 368, 1, 100, 1000, 10000, 100000];

echo "Verificando patrimônios críticos:\n\n";

foreach ($samples as $id) {
    $local_p = $local->query("SELECT NUPATRIMONIO, DEPATRIMONIO, USUARIO, CDPROJETO, CDMATRFUNCIONARIO FROM patr WHERE NUPATRIMONIO = $id")->fetch();
    $kinghost_p = $kinghost->query("SELECT NUPATRIMONIO, DEPATRIMONIO, USUARIO, CDPROJETO, CDMATRFUNCIONARIO FROM patr WHERE NUPATRIMONIO = $id")->fetch();
    
    if (!$local_p && !$kinghost_p) {
        echo "⚠️  Patrimônio $id: não existe em nenhum banco\n";
        continue;
    }
    
    if (!$local_p) {
        echo "⚠️  Patrimônio $id: apenas em KINGHOST (deve ser removido!)\n";
        continue;
    }
    
    if (!$kinghost_p) {
        echo "❌ Patrimônio $id: apenas em LOCAL (falta em KINGHOST!)\n";
        continue;
    }
    
    // Comparar campos
    $match = true;
    $diffs = [];
    
    foreach (['NUPATRIMONIO', 'DEPATRIMONIO', 'USUARIO', 'CDPROJETO', 'CDMATRFUNCIONARIO'] as $field) {
        if ($local_p[$field] != $kinghost_p[$field]) {
            $match = false;
            $diffs[] = "$field: LOCAL='{$local_p[$field]}' vs KH='{$kinghost_p[$field]}'";
        }
    }
    
    if ($match) {
        echo "✅ Patrimônio $id: OK - {$local_p['USUARIO']} / {$local_p['DEPATRIMONIO']}\n";
    } else {
        echo "⚠️  Patrimônio $id: DIVERGÊNCIAS!\n";
        foreach ($diffs as $diff) {
            echo "   - $diff\n";
        }
    }
}

// Verificar distribuição por usuário
echo "\n" . str_repeat("─", 70) . "\n\n";
echo "Distribuição de patrimônios por usuário:\n\n";

$local_users = $local->query('SELECT USUARIO, COUNT(*) as cnt FROM patr GROUP BY USUARIO ORDER BY cnt DESC LIMIT 5')->fetchAll();
$kinghost_users = $kinghost->query('SELECT USUARIO, COUNT(*) as cnt FROM patr GROUP BY USUARIO ORDER BY cnt DESC LIMIT 5')->fetchAll();

echo "LOCAL:\n";
foreach ($local_users as $u) {
    printf("  %-20s: %5d patrimônios\n", $u['USUARIO'], $u['cnt']);
}

echo "\nKINGHOST:\n";
foreach ($kinghost_users as $u) {
    printf("  %-20s: %5d patrimônios\n", $u['USUARIO'], $u['cnt']);
}

echo "\n✅ VERIFICAÇÃO CONCLUÍDA!\n";
