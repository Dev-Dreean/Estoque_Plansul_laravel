<?php
/**
 * SCRIPT SIMPLES DE IMPORTAÇÃO - KINGHOST
 * Baseado no teste que funcionou com 50 registros
 * Sem frameworks, sem complicação, só PDO direto
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('max_execution_time', 0);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  IMPORTAÇÃO SIMPLES - KINGHOST                            ║\n";
echo "║  " . date('d/m/Y H:i:s') . "                                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Conexão direta
try {
    $pdo = new PDO(
        'mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4',
        'plansul004_add2',
        'A33673170a',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Conectado ao banco\n\n";
} catch (PDOException $e) {
    die("❌ Erro de conexão: " . $e->getMessage() . "\n");
}

// ============================================================================
// ARQUIVO DE PATRIMÔNIOS
// ============================================================================
$arquivo = __DIR__ . '/../Patrimonio_NOVO.TXT';
if (!file_exists($arquivo)) {
    die("❌ Arquivo não encontrado: $arquivo\n");
}

$linhas = file($arquivo, FILE_IGNORE_NEW_LINES);
$total = count($linhas);
echo "📄 Arquivo carregado: $total linhas\n";

// Contadores
$atualizados = 0;
$novos = 0;
$erros = 0;
$pulados = 0;

echo "⏳ Processando...\n\n";

// Processar cada linha (pular cabeçalho linhas 0 e 1)
for ($i = 2; $i < $total; $i++) {
    $linha = $linhas[$i];
    
    // Pular linhas pequenas ou separadores
    if (strlen(trim($linha)) < 50 || strpos($linha, '===') !== false) {
        $pulados++;
        continue;
    }
    
    // Converter encoding
    if (!mb_check_encoding($linha, 'UTF-8')) {
        $linha = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $linha);
    }
    
    // Extrair dados por posição fixa (588 chars)
    $nupatrimonio = trim(substr($linha, 0, 16));
    
    // Validar número
    if (!is_numeric($nupatrimonio)) {
        $pulados++;
        continue;
    }
    
    $nupatrimonio = (int)$nupatrimonio;
    
    // Extrair outros campos
    $situacao = trim(substr($linha, 16, 35));
    $marca = trim(substr($linha, 51, 35));
    $cdlocal = trim(substr($linha, 86, 11));
    $modelo = trim(substr($linha, 97, 35));
    $cor = trim(substr($linha, 132, 20));
    $dtaquisicao_raw = trim(substr($linha, 152, 11));
    $depatrimonio = trim(substr($linha, 163, 285));
    $cdfunc = trim(substr($linha, 448, 18));
    $cdprojeto = trim(substr($linha, 466, 13));
    $usuario = trim(substr($linha, 494, 15));
    $cdobjeto = trim(substr($linha, 533, 13));
    
    // Limpar valores <null>
    $situacao = ($situacao === '<null>' || $situacao === '') ? 'EM USO' : $situacao;
    $marca = ($marca === '<null>') ? '' : $marca;
    $cor = ($cor === '<null>') ? '' : $cor;
    $usuario = ($usuario === '<null>' || $usuario === '') ? 'SISTEMA' : $usuario;
    $cdlocal = is_numeric($cdlocal) ? (int)$cdlocal : 1;
    $cdfunc = is_numeric($cdfunc) ? (int)$cdfunc : null;
    $cdprojeto = is_numeric($cdprojeto) ? (int)$cdprojeto : null;
    $cdobjeto = is_numeric($cdobjeto) ? (int)$cdobjeto : null;
    
    // Converter data
    $dtaquisicao = null;
    if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dtaquisicao_raw, $m)) {
        $dtaquisicao = "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    
    try {
        // Verificar se existe
        $check = $pdo->prepare("SELECT NUSEQPATR FROM patr WHERE NUPATRIMONIO = ? LIMIT 1");
        $check->execute([$nupatrimonio]);
        $existe = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($existe) {
            // UPDATE
            $sql = "UPDATE patr SET 
                DEPATRIMONIO = ?, SITUACAO = ?, MARCA = ?, MODELO = ?, COR = ?,
                CDLOCAL = ?, CDMATRFUNCIONARIO = ?, CDPROJETO = ?, CODOBJETO = ?, 
                USUARIO = ?, DTAQUISICAO = ?
                WHERE NUPATRIMONIO = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $depatrimonio, $situacao, $marca, $modelo, $cor,
                $cdlocal, $cdfunc, $cdprojeto, $cdobjeto,
                $usuario, $dtaquisicao,
                $nupatrimonio
            ]);
            
            if ($stmt->rowCount() > 0) {
                $atualizados++;
            }
        } else {
            // INSERT
            $sql = "INSERT INTO patr (
                NUPATRIMONIO, DEPATRIMONIO, SITUACAO, MARCA, MODELO, COR,
                CDLOCAL, CDMATRFUNCIONARIO, CDPROJETO, CODOBJETO, USUARIO, DTAQUISICAO
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nupatrimonio, $depatrimonio, $situacao, $marca, $modelo, $cor,
                $cdlocal, $cdfunc, $cdprojeto, $cdobjeto, $usuario, $dtaquisicao
            ]);
            $novos++;
        }
        
        // Feedback a cada 500 registros
        $processados = $atualizados + $novos;
        if ($processados > 0 && $processados % 500 == 0) {
            echo "📊 $processados processados (atualizados: $atualizados, novos: $novos)\n";
        }
        
    } catch (PDOException $e) {
        $erros++;
        if ($erros <= 5) {
            echo "⚠️ Erro #$nupatrimonio: " . substr($e->getMessage(), 0, 80) . "\n";
        }
    }
}

// Resultado final
echo "\n";
echo "════════════════════════════════════════════════════════════\n";
echo "✅ IMPORTAÇÃO CONCLUÍDA!\n";
echo "════════════════════════════════════════════════════════════\n";
echo "📊 Atualizados: $atualizados\n";
echo "📊 Novos: $novos\n";
echo "📊 Erros: $erros\n";
echo "📊 Pulados: $pulados\n";
echo "\n";

// Verificar alguns registros específicos
echo "🔍 Verificando registros importantes:\n";
$verificar = [3, 38, 45, 100, 5640];
foreach ($verificar as $num) {
    $stmt = $pdo->query("SELECT NUPATRIMONIO, SITUACAO, CDPROJETO, USUARIO FROM patr WHERE NUPATRIMONIO = $num");
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        echo "  #$num: SITUACAO={$r['SITUACAO']}, CDPROJETO={$r['CDPROJETO']}, USUARIO={$r['USUARIO']}\n";
    }
}

echo "\n✅ Finalizado!\n";
