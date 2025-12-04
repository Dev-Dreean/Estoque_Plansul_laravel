<?php
/**
 * SINCRONIZAÇÃO KINGHOST ← LOCAL
 * 
 * Este script sincroniza os dados do KingHost com o banco LOCAL
 * Usa REPLACE INTO para atualizar registros existentes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  SINCRONIZAÇÃO KINGHOST ← LOCAL                          ║\n";
echo "║  " . date('d/m/Y H:i:s') . "                                          ║\n";
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
// SINCRONIZAR TABFANT (PROJETOS)
// ============================================================
echo "═══════════════════════════════════════════════════════════\n";
echo "SINCRONIZANDO: PROJETOS (tabfant)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$projetos = $local->query("SELECT * FROM tabfant ORDER BY CDPROJETO")->fetchAll(PDO::FETCH_ASSOC);

$atualizados = 0;
$inseridos = 0;

foreach ($projetos as $p) {
    try {
        // Verificar se existe
        $existe = $kinghost->query("SELECT 1 FROM tabfant WHERE CDPROJETO = {$p['CDPROJETO']} LIMIT 1")->fetch();
        
        if ($existe) {
            // UPDATE
            $sql = "UPDATE tabfant SET NOMEPROJETO = ? WHERE CDPROJETO = ?";
            $stmt = $kinghost->prepare($sql);
            $stmt->execute([(string)$p['NOMEPROJETO'], $p['CDPROJETO']]);
            $atualizados++;
        } else {
            // INSERT
            $sql = "INSERT INTO tabfant (CDPROJETO, NOMEPROJETO) VALUES (?, ?)";
            $stmt = $kinghost->prepare($sql);
            $stmt->execute([$p['CDPROJETO'], (string)$p['NOMEPROJETO']]);
            $inseridos++;
        }
    } catch (Exception $e) {
        echo "⚠️  Erro no projeto {$p['CDPROJETO']}: " . $e->getMessage() . "\n";
    }
}

echo "✅ Atualizados: $atualizados\n";
echo "✅ Inseridos: $inseridos\n\n";

// ============================================================
// SINCRONIZAR LOCAIS_PROJETO (LOCAIS)
// ============================================================
echo "═══════════════════════════════════════════════════════════\n";
echo "SINCRONIZANDO: LOCAIS (locais_projeto)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$locais = $local->query("SELECT * FROM locais_projeto ORDER BY cdlocal")->fetchAll(PDO::FETCH_ASSOC);

$atualizados = 0;
$inseridos = 0;

foreach ($locais as $l) {
    try {
        $existe = $kinghost->query("SELECT 1 FROM locais_projeto WHERE cdlocal = {$l['cdlocal']} LIMIT 1")->fetch();
        
        if ($existe) {
            // UPDATE
            $sql = "UPDATE locais_projeto SET delocal = ?, codigo_projeto = ?, flativo = ? WHERE cdlocal = ?";
            $stmt = $kinghost->prepare($sql);
            $stmt->execute([(string)$l['delocal'], $l['codigo_projeto'], $l['flativo'] ?? 1, $l['cdlocal']]);
            $atualizados++;
        } else {
            // INSERT
            $sql = "INSERT INTO locais_projeto (cdlocal, delocal, codigo_projeto, flativo) VALUES (?, ?, ?, ?)";
            $stmt = $kinghost->prepare($sql);
            $stmt->execute([$l['cdlocal'], (string)$l['delocal'], $l['codigo_projeto'], $l['flativo'] ?? 1]);
            $inseridos++;
        }
    } catch (Exception $e) {
        echo "⚠️  Erro no local {$l['cdlocal']}: " . $e->getMessage() . "\n";
    }
}

echo "✅ Atualizados: $atualizados\n";
echo "✅ Inseridos: $inseridos\n\n";

// ============================================================
// SINCRONIZAR PATRIMÔNIOS (patr) - MAIS CRÍTICO
// ============================================================
echo "═══════════════════════════════════════════════════════════\n";
echo "SINCRONIZANDO: PATRIMÔNIOS (patr)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Primeiro, encontrar patrimônios que faltam ou têm divergências
$sql = "SELECT p.* FROM patr p WHERE p.NUPATRIMONIO NOT IN (
    SELECT k.NUPATRIMONIO FROM (
        SELECT DISTINCT NUPATRIMONIO FROM patr LIMIT 99999999
    ) k
) UNION SELECT p.* FROM patr p LIMIT 11400";

// Mais simples: buscar todos e atualizar
$patrimonios = $local->query("SELECT NUPATRIMONIO, SITUACAO, MARCA, MODELO, COR, CDLOCAL, 
    CDMATRFUNCIONARIO, CDPROJETO, CODOBJETO, USUARIO, DEPATRIMONIO, DTAQUISICAO 
    FROM patr ORDER BY NUPATRIMONIO")->fetchAll(PDO::FETCH_ASSOC);

$atualizados = 0;
$inseridos = 0;
$total = count($patrimonios);

foreach ($patrimonios as $idx => $p) {
    try {
        $existe = $kinghost->query("SELECT 1 FROM patr WHERE NUPATRIMONIO = {$p['NUPATRIMONIO']} LIMIT 1")->fetch();
        
        if ($existe) {
            // UPDATE - atualizar todos os campos críticos
            $sql = "UPDATE patr SET SITUACAO = ?, MARCA = ?, MODELO = ?, COR = ?, CDLOCAL = ?, 
                    CDMATRFUNCIONARIO = ?, CDPROJETO = ?, CODOBJETO = ?, USUARIO = ?, 
                    DEPATRIMONIO = ?, DTAQUISICAO = ? WHERE NUPATRIMONIO = ?";
            $stmt = $kinghost->prepare($sql);
            $stmt->execute([
                (string)$p['SITUACAO'],
                (string)$p['MARCA'],
                (string)$p['MODELO'],
                (string)$p['COR'],
                $p['CDLOCAL'],
                $p['CDMATRFUNCIONARIO'],
                $p['CDPROJETO'],
                $p['CODOBJETO'],
                (string)$p['USUARIO'],
                (string)$p['DEPATRIMONIO'],
                (string)$p['DTAQUISICAO'],
                $p['NUPATRIMONIO']
            ]);
            $atualizados++;
        } else {
            // INSERT
            $sql = "INSERT INTO patr (NUPATRIMONIO, SITUACAO, MARCA, MODELO, COR, CDLOCAL, 
                    CDMATRFUNCIONARIO, CDPROJETO, CODOBJETO, USUARIO, DEPATRIMONIO, DTAQUISICAO) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $kinghost->prepare($sql);
            $stmt->execute([
                $p['NUPATRIMONIO'],
                (string)$p['SITUACAO'],
                (string)$p['MARCA'],
                (string)$p['MODELO'],
                (string)$p['COR'],
                $p['CDLOCAL'],
                $p['CDMATRFUNCIONARIO'],
                $p['CDPROJETO'],
                $p['CODOBJETO'],
                (string)$p['USUARIO'],
                (string)$p['DEPATRIMONIO'],
                (string)$p['DTAQUISICAO']
            ]);
            $inseridos++;
        }
        
        if (($idx + 1) % 1000 == 0) {
            echo "  ✅ Processados: " . ($idx + 1) . " de $total\n";
        }
    } catch (Exception $e) {
        echo "⚠️  Erro no patrimônio {$p['NUPATRIMONIO']}: " . $e->getMessage() . "\n";
    }
}

echo "✅ Atualizados: $atualizados\n";
echo "✅ Inseridos: $inseridos\n\n";

// ============================================================
// SINCRONIZAR HISTÓRICO (movpartr)
// ============================================================
echo "═══════════════════════════════════════════════════════════\n";
echo "SINCRONIZANDO: HISTÓRICO (movpartr)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$movimentacoes = $local->query("SELECT * FROM movpartr ORDER BY NUPATR, DTOPERACAO")->fetchAll(PDO::FETCH_ASSOC);

$atualizados = 0;
$inseridos = 0;
$total = count($movimentacoes);

foreach ($movimentacoes as $idx => $m) {
    try {
        // Verificar se existe (por NUPATR + DTOPERACAO + USUARIO)
        $existe = $kinghost->query("SELECT 1 FROM movpartr WHERE NUPATR = {$m['NUPATR']} 
            AND DTOPERACAO = '{$m['DTOPERACAO']}' AND USUARIO = '{$m['USUARIO']}' LIMIT 1")->fetch();
        
        if (!$existe) {
            // INSERT
            $sql = "INSERT INTO movpartr (NUPATR, CODPROJ, DTOPERACAO, USUARIO) VALUES (?, ?, ?, ?)";
            $stmt = $kinghost->prepare($sql);
            $stmt->execute([
                $m['NUPATR'],
                $m['CODPROJ'],
                $m['DTOPERACAO'],
                (string)$m['USUARIO']
            ]);
            $inseridos++;
        }
        
        if (($idx + 1) % 500 == 0) {
            echo "  ✅ Processados: " . ($idx + 1) . " de $total\n";
        }
    } catch (Exception $e) {
        echo "⚠️  Erro: " . $e->getMessage() . "\n";
    }
}

echo "✅ Inseridos: $inseridos\n\n";

// ============================================================
// RESULTADO FINAL
// ============================================================
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  SINCRONIZAÇÃO CONCLUÍDA!                                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Verificar contagens finais
$count_tabfant_local = $local->query("SELECT COUNT(*) as cnt FROM tabfant")->fetch(PDO::FETCH_ASSOC)['cnt'];
$count_tabfant_kinghost = $kinghost->query("SELECT COUNT(*) as cnt FROM tabfant")->fetch(PDO::FETCH_ASSOC)['cnt'];

$count_locais_local = $local->query("SELECT COUNT(*) as cnt FROM locais_projeto")->fetch(PDO::FETCH_ASSOC)['cnt'];
$count_locais_kinghost = $kinghost->query("SELECT COUNT(*) as cnt FROM locais_projeto")->fetch(PDO::FETCH_ASSOC)['cnt'];

$count_patr_local = $local->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];
$count_patr_kinghost = $kinghost->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];

$count_mov_local = $local->query("SELECT COUNT(*) as cnt FROM movpartr")->fetch(PDO::FETCH_ASSOC)['cnt'];
$count_mov_kinghost = $kinghost->query("SELECT COUNT(*) as cnt FROM movpartr")->fetch(PDO::FETCH_ASSOC)['cnt'];

echo "CONTAGENS FINAIS:\n";
echo "PROJETOS:    LOCAL=$count_tabfant_local vs KINGHOST=$count_tabfant_kinghost\n";
echo "LOCAIS:      LOCAL=$count_locais_local vs KINGHOST=$count_locais_kinghost\n";
echo "PATRIMÔNIOS: LOCAL=$count_patr_local vs KINGHOST=$count_patr_kinghost\n";
echo "HISTÓRICO:   LOCAL=$count_mov_local vs KINGHOST=$count_mov_kinghost\n";

if ($count_tabfant_local == $count_tabfant_kinghost && 
    $count_locais_local == $count_locais_kinghost && 
    $count_patr_local == $count_patr_kinghost && 
    $count_mov_local == $count_mov_kinghost) {
    echo "\n🎉 TODOS OS BANCOS SINCRONIZADOS COM SUCESSO!\n";
} else {
    echo "\n⚠️  AINDA HÁ DIFERENÇAS - Verifique acima\n";
}
