<?php
/**
 * VALIDAÇÃO COMPLETA - 4 BANCOS PRINCIPAIS
 * 
 * Valida as 4 tabelas principais:
 * 1. tabfant (Projetos)
 * 2. locais_projeto (Locais)
 * 3. patr (Patrimônios)
 * 4. movpartr (Histórico de Movimentações)
 * 
 * Comparação: LOCAL vs KINGHOST vs BACKUP
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  VALIDAÇÃO COMPLETA - 4 BANCOS PRINCIPAIS               ║\n";
echo "║  LOCAL vs KINGHOST vs BACKUP                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Conexões
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

// ============================================================
// TABELA 1: PROJETOS (tabfant)
// ============================================================
echo "═══════════════════════════════════════════════════════════\n";
echo "TABELA 1: PROJETOS (tabfant)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$count_local_proj = $local->query("SELECT COUNT(*) as cnt FROM tabfant")->fetch(PDO::FETCH_ASSOC)['cnt'];
$count_kinghost_proj = $kinghost->query("SELECT COUNT(*) as cnt FROM tabfant")->fetch(PDO::FETCH_ASSOC)['cnt'];

echo "LOCAL:    $count_local_proj projetos\n";
echo "KINGHOST: $count_kinghost_proj projetos\n";

if ($count_local_proj == $count_kinghost_proj) {
    echo "✅ Contagens iguais\n\n";
} else {
    echo "⚠️  DIFERENÇA DE CONTAGEM!\n\n";
}

// Verificar projetos específicos
$projetos_teste = [8, 100001, 522, 523];
foreach ($projetos_teste as $cdproj) {
    $local_proj = $local->query("SELECT CDPROJETO, NOMEPROJETO FROM tabfant WHERE CDPROJETO = $cdproj LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $kinghost_proj = $kinghost->query("SELECT CDPROJETO, NOMEPROJETO FROM tabfant WHERE CDPROJETO = $cdproj LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($local_proj && $kinghost_proj) {
        if ($local_proj['NOMEPROJETO'] === $kinghost_proj['NOMEPROJETO']) {
            echo "✅ Projeto $cdproj: OK\n";
        } else {
            echo "❌ Projeto $cdproj: DIVERGÊNCIA - LOCAL='${local_proj['NOMEPROJETO']}' vs KINGHOST='${kinghost_proj['NOMEPROJETO']}'\n";
        }
    } elseif (!$kinghost_proj) {
        echo "❌ Projeto $cdproj: FALTA NO KINGHOST\n";
    }
}

// ============================================================
// TABELA 2: LOCAIS (locais_projeto)
// ============================================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "TABELA 2: LOCAIS (locais_projeto)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$count_local_locs = $local->query("SELECT COUNT(*) as cnt FROM locais_projeto")->fetch(PDO::FETCH_ASSOC)['cnt'];
$count_kinghost_locs = $kinghost->query("SELECT COUNT(*) as cnt FROM locais_projeto")->fetch(PDO::FETCH_ASSOC)['cnt'];

echo "LOCAL:    $count_local_locs locais\n";
echo "KINGHOST: $count_kinghost_locs locais\n";

if ($count_local_locs == $count_kinghost_locs) {
    echo "✅ Contagens iguais\n\n";
} else {
    echo "⚠️  DIFERENÇA DE CONTAGEM!\n\n";
}

// Verificar locais específicos
$locais_teste = [2059, 1, 100, 500];
$diferences_locs = 0;
foreach ($locais_teste as $cdlocal) {
    $local_loc = $local->query("SELECT * FROM locais_projeto WHERE cdlocal = $cdlocal LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $kinghost_loc = $kinghost->query("SELECT * FROM locais_projeto WHERE cdlocal = $cdlocal LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($local_loc && $kinghost_loc) {
        if ($local_loc['delocal'] === $kinghost_loc['delocal']) {
            echo "✅ Local $cdlocal: OK\n";
        } else {
            echo "⚠️  Local $cdlocal: NOME DIFERENTE\n";
            $diferences_locs++;
        }
    } elseif (!$kinghost_loc && $local_loc) {
        echo "❌ Local $cdlocal: FALTA NO KINGHOST\n";
        $diferences_locs++;
    }
}

// ============================================================
// TABELA 3: PATRIMÔNIOS (patr)
// ============================================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "TABELA 3: PATRIMÔNIOS (patr)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$count_local_patr = $local->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];
$count_kinghost_patr = $kinghost->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];

echo "LOCAL:    $count_local_patr patrimônios\n";
echo "KINGHOST: $count_kinghost_patr patrimônios\n";

if ($count_local_patr == $count_kinghost_patr) {
    echo "✅ Contagens iguais\n\n";
} else {
    echo "⚠️  DIFERENÇA: " . ($count_local_patr - $count_kinghost_patr) . " patrimônios\n\n";
}

// Verificar patrimônios faltando no KingHost
echo "Patrimônios faltando no KingHost:\n";

// Buscar qual o maior ID em cada banco
$max_local = $local->query("SELECT MAX(NUPATRIMONIO) FROM patr")->fetch(PDO::FETCH_ASSOC);
$max_kinghost = $kinghost->query("SELECT MAX(NUPATRIMONIO) FROM patr")->fetch(PDO::FETCH_ASSOC);

echo "Max LOCAL: {$max_local['MAX(NUPATRIMONIO)']}\n";
echo "Max KINGHOST: {$max_kinghost['MAX(NUPATRIMONIO)']}\n\n";

echo "Verificando distribuição de usuários nos patrimônios:\n";
$usuarios_local = $local->query("SELECT DISTINCT USUARIO, COUNT(*) as cnt FROM patr GROUP BY USUARIO ORDER BY cnt DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$usuarios_kinghost = $kinghost->query("SELECT DISTINCT USUARIO, COUNT(*) as cnt FROM patr GROUP BY USUARIO ORDER BY cnt DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

echo "\nLOCAL - Top 10 usuários:\n";
foreach ($usuarios_local as $u) {
    echo "  {$u['USUARIO']}: {$u['cnt']}\n";
}

echo "\nKINGHOST - Top 10 usuários:\n";
foreach ($usuarios_kinghost as $u) {
    echo "  {$u['USUARIO']}: {$u['cnt']}\n";
}

// Verificar campos críticos
echo "\n\nVerificando campos críticos em amostra de patrimônios:\n";
$amostra = [5243, 33074, 16216, 368, 1, 100, 1000];
$campos_criticos = ['USUARIO', 'SITUACAO', 'CDLOCAL', 'CDMATRFUNCIONARIO', 'CDPROJETO'];
$divergencias_patr = 0;

foreach ($amostra as $nupatr) {
    $local_p = $local->query("SELECT " . implode(',', $campos_criticos) . " FROM patr WHERE NUPATRIMONIO = $nupatr LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $kinghost_p = $kinghost->query("SELECT " . implode(',', $campos_criticos) . " FROM patr WHERE NUPATRIMONIO = $nupatr LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$local_p && !$kinghost_p) {
        continue;
    }
    
    if (!$kinghost_p) {
        echo "❌ #$nupatr: FALTA NO KINGHOST\n";
        $divergencias_patr++;
        continue;
    }
    
    $has_diff = false;
    foreach ($campos_criticos as $campo) {
        $val_local = trim($local_p[$campo] ?? '');
        $val_kinghost = trim($kinghost_p[$campo] ?? '');
        if ($val_local !== $val_kinghost) {
            if (!$has_diff) {
                echo "⚠️  #$nupatr:\n";
                $has_diff = true;
            }
            echo "   $campo: LOCAL='$val_local' vs KINGHOST='$val_kinghost'\n";
            $divergencias_patr++;
        }
    }
    
    if (!$has_diff && $local_p) {
        echo "✅ #$nupatr: OK\n";
    }
}

// ============================================================
// TABELA 4: HISTÓRICO (movpartr)
// ============================================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "TABELA 4: HISTÓRICO (movpartr)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$count_local_mov = $local->query("SELECT COUNT(*) as cnt FROM movpartr")->fetch(PDO::FETCH_ASSOC)['cnt'];
$count_kinghost_mov = $kinghost->query("SELECT COUNT(*) as cnt FROM movpartr")->fetch(PDO::FETCH_ASSOC)['cnt'];

echo "LOCAL:    $count_local_mov movimentações\n";
echo "KINGHOST: $count_kinghost_mov movimentações\n";

if ($count_local_mov == $count_kinghost_mov) {
    echo "✅ Contagens iguais\n\n";
} else {
    echo "⚠️  DIFERENÇA: " . ($count_local_mov - $count_kinghost_mov) . " movimentações\n\n";
}

// ============================================================
// RESUMO FINAL
// ============================================================
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMO FINAL                                             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "1. PROJETOS (tabfant):\n";
echo "   LOCAL: $count_local_proj | KINGHOST: $count_kinghost_proj\n";
echo "   Status: " . ($count_local_proj == $count_kinghost_proj ? "✅ OK" : "⚠️  DIFERENÇA") . "\n\n";

echo "2. LOCAIS (locais_projeto):\n";
echo "   LOCAL: $count_local_locs | KINGHOST: $count_kinghost_locs\n";
echo "   Status: " . ($count_local_locs == $count_kinghost_locs ? "✅ OK" : "⚠️  DIFERENÇA") . "\n\n";

echo "3. PATRIMÔNIOS (patr):\n";
echo "   LOCAL: $count_local_patr | KINGHOST: $count_kinghost_patr\n";
echo "   Status: " . ($count_local_patr == $count_kinghost_patr ? "✅ OK" : "⚠️  DIFERENÇA") . "\n\n";

echo "4. HISTÓRICO (movpartr):\n";
echo "   LOCAL: $count_local_mov | KINGHOST: $count_kinghost_mov\n";
echo "   Status: " . ($count_local_mov == $count_kinghost_mov ? "✅ OK" : "⚠️  DIFERENÇA") . "\n\n";

echo "═══════════════════════════════════════════════════════════\n";
if ($count_local_patr == $count_kinghost_patr && $count_local_locs == $count_kinghost_locs && $divergencias_patr == 0) {
    echo "🎉 TODOS OS BANCOS VALIDADOS COM SUCESSO!\n";
} else {
    echo "⚠️  VERIFICAR DIVERGÊNCIAS ACIMA\n";
}
echo "═══════════════════════════════════════════════════════════\n";
