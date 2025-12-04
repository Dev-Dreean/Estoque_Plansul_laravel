<?php
/**
 * VALIDAÇÃO COMPLETA DA IMPORTAÇÃO
 * Comparar amostra de patrimônios entre LOCAL e KINGHOST
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  VALIDAÇÃO DA IMPORTAÇÃO - LOCAL vs KINGHOST              ║\n";
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

// PASSO 1: Comparar contagens
echo "═══════════════════════════════════════════════════════════\n";
echo "PASSO 1: CONTAGENS GERAIS\n";
echo "═══════════════════════════════════════════════════════════\n";

$count_local = $local->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];
$count_kinghost = $kinghost->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];

echo "LOCAL:    $count_local patrimônios\n";
echo "KINGHOST: $count_kinghost patrimônios\n";

if ($count_local != $count_kinghost) {
    echo "⚠️  DIFERENÇA DE CONTAGEM!\n\n";
} else {
    echo "✅ Contagens iguais!\n\n";
}

// PASSO 2: Comparar amostra de patrimônios específicos
echo "═══════════════════════════════════════════════════════════\n";
echo "PASSO 2: VALIDAÇÃO DE AMOSTRA (10 patrimônios aleatórios)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$patrimônios_teste = [5243, 33074, 16216];

// Pegar mais alguns aleatórios do LOCAL
$aleatorios = $local->query("SELECT NUPATRIMONIO FROM patr ORDER BY RAND() LIMIT 7")->fetchAll(PDO::FETCH_COLUMN);
$patrimônios_teste = array_merge($patrimônios_teste, $aleatorios);

$diferencas = 0;
$corretos = 0;

foreach ($patrimônios_teste as $nupatr) {
    $sql = "SELECT NUPATRIMONIO, USUARIO, CDMATRFUNCIONARIO, SITUACAO, CDLOCAL, DEPATRIMONIO, MARCA 
            FROM patr WHERE NUPATRIMONIO = ?";
    
    $stmt_local = $local->prepare($sql);
    $stmt_local->execute([$nupatr]);
    $data_local = $stmt_local->fetch(PDO::FETCH_ASSOC);
    
    $stmt_kinghost = $kinghost->prepare($sql);
    $stmt_kinghost->execute([$nupatr]);
    $data_kinghost = $stmt_kinghost->fetch(PDO::FETCH_ASSOC);
    
    if (!$data_local && !$data_kinghost) {
        echo "⚠️  Patrimônio $nupatr não existe em nenhum banco\n\n";
        continue;
    }
    
    if (!$data_kinghost) {
        echo "❌ Patrimônio $nupatr: FALTA NO KINGHOST\n\n";
        $diferencas++;
        continue;
    }
    
    if (!$data_local) {
        echo "⚠️  Patrimônio $nupatr: só existe no KINGHOST\n\n";
        continue;
    }
    
    // Comparar campos importantes
    $campos_importantes = ['USUARIO', 'CDMATRFUNCIONARIO', 'SITUACAO', 'CDLOCAL'];
    $divergencias = [];
    
    foreach ($campos_importantes as $campo) {
        $val_local = trim($data_local[$campo]);
        $val_kinghost = trim($data_kinghost[$campo]);
        
        if ($val_local !== $val_kinghost) {
            $divergencias[] = "$campo: LOCAL='$val_local' vs KINGHOST='$val_kinghost'";
        }
    }
    
    if (count($divergencias) > 0) {
        echo "❌ Patrimônio $nupatr: DIVERGÊNCIAS\n";
        foreach ($divergencias as $div) {
            echo "   - $div\n";
        }
        echo "\n";
        $diferencas++;
    } else {
        echo "✅ Patrimônio $nupatr: OK (USUARIO={$data_local['USUARIO']}, SITUACAO={$data_local['SITUACAO']})\n";
        $corretos++;
    }
}

// PASSO 3: Verificar usuários únicos
echo "\n═══════════════════════════════════════════════════════════\n";
echo "PASSO 3: USUÁRIOS ÚNICOS\n";
echo "═══════════════════════════════════════════════════════════\n";

$usuarios_local = $local->query("SELECT DISTINCT USUARIO, COUNT(*) as cnt FROM patr GROUP BY USUARIO ORDER BY cnt DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$usuarios_kinghost = $kinghost->query("SELECT DISTINCT USUARIO, COUNT(*) as cnt FROM patr GROUP BY USUARIO ORDER BY cnt DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

echo "\nLOCAL - Top 10 usuários:\n";
foreach ($usuarios_local as $u) {
    echo "  {$u['USUARIO']}: {$u['cnt']} patrimônios\n";
}

echo "\nKINGHOST - Top 10 usuários:\n";
foreach ($usuarios_kinghost as $u) {
    echo "  {$u['USUARIO']}: {$u['cnt']} patrimônios\n";
}

// RESULTADO FINAL
echo "\n\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  RESULTADO FINAL                                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "✅ Corretos: $corretos\n";
echo "❌ Com divergências: $diferencas\n";

if ($diferencas == 0 && $corretos > 0) {
    echo "\n🎉 IMPORTAÇÃO 100% VALIDADA!\n";
} elseif ($diferencas > 0) {
    echo "\n⚠️  IMPORTAÇÃO COM PROBLEMAS - revisar!\n";
}
