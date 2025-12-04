<?php
/**
 * SINCRONIZAR LOCAL → KINGHOST
 * Executado LOCALMENTE para sincronizar dados para o servidor
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  SINCRONIZAÇÃO LOCAL → KINGHOST                          ║\n";
echo "║  " . date('d/m/Y H:i:s') . "                                          ║\n";
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

// ============================================================
// SINCRONIZAR PATRIMÔNIOS - PRIORIDADE MÁXIMA
// ============================================================
echo "═══════════════════════════════════════════════════════════\n";
echo "SINCRONIZANDO: PATRIMÔNIOS (patr)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Buscar todos os patrimônios do LOCAL
$patrimonios = $local->query("SELECT NUPATRIMONIO, SITUACAO, MARCA, MODELO, COR, CDLOCAL, 
    CDMATRFUNCIONARIO, CDPROJETO, CODOBJETO, USUARIO, DEPATRIMONIO, DTAQUISICAO 
    FROM patr ORDER BY NUPATRIMONIO")->fetchAll(PDO::FETCH_ASSOC);

$atualizados = 0;
$inseridos = 0;
$total = count($patrimonios);
$erros = 0;

echo "📊 Total de patrimônios para sincronizar: $total\n\n";

foreach ($patrimonios as $idx => $p) {
    try {
        $exist_check = $kinghost->prepare("SELECT COUNT(*) as cnt FROM patr WHERE NUPATRIMONIO = ?");
        $exist_check->execute([$p['NUPATRIMONIO']]);
        $exists = $exist_check->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
        
        if ($exists) {
            // UPDATE - atualizar todos os campos
            $sql = "UPDATE patr SET 
                SITUACAO = ?, MARCA = ?, MODELO = ?, COR = ?, CDLOCAL = ?, 
                CDMATRFUNCIONARIO = ?, CDPROJETO = ?, CODOBJETO = ?, USUARIO = ?, 
                DEPATRIMONIO = ?, DTAQUISICAO = ?
                WHERE NUPATRIMONIO = ?";
            $stmt = $kinghost->prepare($sql);
            $result = $stmt->execute([
                (string)($p['SITUACAO'] ?? 'EM USO'),
                (string)($p['MARCA'] ?? ''),
                (string)($p['MODELO'] ?? ''),
                (string)($p['COR'] ?? ''),
                (int)($p['CDLOCAL'] ?? 1),
                (int)($p['CDMATRFUNCIONARIO'] ?? 133838),
                (int)($p['CDPROJETO'] ?? 8),
                ($p['CODOBJETO'] ?? null),
                (string)($p['USUARIO'] ?? 'SISTEMA'),
                (string)($p['DEPATRIMONIO'] ?? ''),
                (string)($p['DTAQUISICAO'] ?? null),
                $p['NUPATRIMONIO']
            ]);
            if ($result) $atualizados++;
        } else {
            // INSERT
            $sql = "INSERT INTO patr 
                (NUPATRIMONIO, SITUACAO, MARCA, MODELO, COR, CDLOCAL, 
                 CDMATRFUNCIONARIO, CDPROJETO, CODOBJETO, USUARIO, DEPATRIMONIO, DTAQUISICAO) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $kinghost->prepare($sql);
            $result = $stmt->execute([
                $p['NUPATRIMONIO'],
                (string)($p['SITUACAO'] ?? 'EM USO'),
                (string)($p['MARCA'] ?? ''),
                (string)($p['MODELO'] ?? ''),
                (string)($p['COR'] ?? ''),
                (int)($p['CDLOCAL'] ?? 1),
                (int)($p['CDMATRFUNCIONARIO'] ?? 133838),
                (int)($p['CDPROJETO'] ?? 8),
                ($p['CODOBJETO'] ?? null),
                (string)($p['USUARIO'] ?? 'SISTEMA'),
                (string)($p['DEPATRIMONIO'] ?? ''),
                (string)($p['DTAQUISICAO'] ?? null)
            ]);
            if ($result) $inseridos++;
        }
        
        if (($idx + 1) % 1000 == 0) {
            echo "  ✅ Processados: " . ($idx + 1) . " de $total (Atualizados: $atualizados, Inseridos: $inseridos)\n";
        }
    } catch (Exception $e) {
        $erros++;
        if ($erros <= 5) {
            echo "⚠️  Erro #" . $p['NUPATRIMONIO'] . ": " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ PATRIMÔNIOS SINCRONIZADOS:\n";
echo "   Atualizados: $atualizados\n";
echo "   Inseridos: $inseridos\n";
echo "   Erros: $erros\n\n";

// ============================================================
// SINCRONIZAR HISTÓRICO (movpartr)
// ============================================================
echo "═══════════════════════════════════════════════════════════\n";
echo "SINCRONIZANDO: HISTÓRICO (movpartr)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$movimentacoes = $local->query("SELECT * FROM movpartr ORDER BY NUPATR, DTOPERACAO")->fetchAll(PDO::FETCH_ASSOC);

$inseridos_mov = 0;
$total_mov = count($movimentacoes);
$erros_mov = 0;

echo "📊 Total de movimentações para sincronizar: $total_mov\n\n";

foreach ($movimentacoes as $idx => $m) {
    try {
        // Verificar se existe (por NUPATR + DTOPERACAO + USUARIO)
        $check = $kinghost->prepare("SELECT COUNT(*) as cnt FROM movpartr 
            WHERE NUPATR = ? AND DTOPERACAO = ? AND USUARIO = ?");
        $check->execute([$m['NUPATR'], $m['DTOPERACAO'], $m['USUARIO']]);
        $exists = $check->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
        
        if (!$exists) {
            // INSERT
            $sql = "INSERT INTO movpartr (NUPATR, CODPROJ, DTOPERACAO, USUARIO) VALUES (?, ?, ?, ?)";
            $stmt = $kinghost->prepare($sql);
            $stmt->execute([
                (int)$m['NUPATR'],
                (int)($m['CODPROJ'] ?? 8),
                (string)$m['DTOPERACAO'],
                (string)($m['USUARIO'] ?? 'SISTEMA')
            ]);
            $inseridos_mov++;
        }
        
        if (($idx + 1) % 500 == 0) {
            echo "  ✅ Processados: " . ($idx + 1) . " de $total_mov (Inseridos: $inseridos_mov)\n";
        }
    } catch (Exception $e) {
        $erros_mov++;
        if ($erros_mov <= 5) {
            echo "⚠️  Erro: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ HISTÓRICO SINCRONIZADO:\n";
echo "   Inseridos: $inseridos_mov\n";
echo "   Erros: $erros_mov\n\n";

// ============================================================
// VERIFICAR CONTAGENS FINAIS
// ============================================================
echo "═══════════════════════════════════════════════════════════\n";
echo "VERIFICANDO CONTAGENS FINAIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$count_patr_local = $local->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];
$count_patr_kinghost = $kinghost->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];

$count_mov_local = $local->query("SELECT COUNT(*) as cnt FROM movpartr")->fetch(PDO::FETCH_ASSOC)['cnt'];
$count_mov_kinghost = $kinghost->query("SELECT COUNT(*) as cnt FROM movpartr")->fetch(PDO::FETCH_ASSOC)['cnt'];

echo "PATRIMÔNIOS: LOCAL=$count_patr_local vs KINGHOST=$count_patr_kinghost\n";
echo "HISTÓRICO:   LOCAL=$count_mov_local vs KINGHOST=$count_mov_kinghost\n\n";

if ($count_patr_local == $count_patr_kinghost && $count_mov_local == $count_mov_kinghost) {
    echo "🎉 SINCRONIZAÇÃO CONCLUÍDA COM SUCESSO!\n";
} else {
    echo "⚠️  Ainda há diferenças:\n";
    echo "   Patrimônios: diferença de " . ($count_patr_local - $count_patr_kinghost) . "\n";
    echo "   Histórico: diferença de " . ($count_mov_local - $count_mov_kinghost) . "\n";
}
