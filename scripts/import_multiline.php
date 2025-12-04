<?php
/**
 * IMPORTAÇÃO CORRIGIDA - Arquivo com registros em múltiplas linhas
 * Lê o arquivo como BLOCO fixo (não linha por linha)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('max_execution_time', 0);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  IMPORTAÇÃO MULTI-LINHA - KINGHOST                        ║\n";
echo "║  " . date('d/m/Y H:i:s') . "                                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Conexão
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

// Arquivo
$arquivo = __DIR__ . '/../Patrimonio_NOVO.TXT';
if (!file_exists($arquivo)) {
    die("❌ Arquivo não encontrado: $arquivo\n");
}

// LER como BLOB completo (não linha por linha!)
$conteudo = file_get_contents($arquivo);

// Converter encoding
if (!mb_check_encoding($conteudo, 'UTF-8')) {
    $conteudo = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $conteudo);
}

echo "📄 Arquivo carregado\n";

// Encontrar onde começa os dados (após separador de ===)
$posicaoSeparador = strpos($conteudo, '============    ');
if ($posicaoSeparador === false) {
    die("❌ Separador não encontrado\n");
}

// Pegar conteúdo após separador
$dadosRaw = substr($conteudo, $posicaoSeparador + 150);

// Dividir por NUPATRIMONIO (começa sempre com número seguido de espaços em coluna fixa)
// Padrão: linha começa com dígitos (o NUPATRIMONIO)
$registros = [];
$registroAtual = '';
$linhas = explode("\n", $dadosRaw);

foreach ($linhas as $linha) {
    // Se a linha começa com espaços + dígitos (é um novo registro)
    if (preg_match('/^(\d+)\s/', trim($linha)) && strlen(trim($linha)) > 5) {
        // Se já temos um registro anterior, salvar
        if (!empty($registroAtual)) {
            $registros[] = $registroAtual;
        }
        $registroAtual = $linha;
    } else {
        // Continuação do registro anterior
        if (!empty(trim($linha))) {
            $registroAtual .= "\n" . $linha;
        }
    }
}

// Último registro
if (!empty($registroAtual)) {
    $registros[] = $registroAtual;
}

$total = count($registros);
echo "📊 Registros detectados: $total\n";
echo "⏳ Processando...\n\n";

// Contadores
$atualizados = 0;
$novos = 0;
$erros = 0;

// Processar cada registro
foreach ($registros as $idx => $bloco) {
    try {
        // Extrair NUPATRIMONIO (primeiro número da primeira linha)
        if (!preg_match('/^(\d+)/', trim($bloco), $m)) {
            continue;
        }
        
        $nupatrimonio = (int)$m[1];
        
        // Extrair campos por posição (da PRIMEIRA linha do bloco)
        $primeiraLinha = explode("\n", $bloco)[0];
        
        $situacao = trim(substr($primeiraLinha, 16, 35));
        $marca = trim(substr($primeiraLinha, 51, 35));
        $cdlocal = trim(substr($primeiraLinha, 86, 11));
        $modelo = trim(substr($primeiraLinha, 97, 35));
        $cor = trim(substr($primeiraLinha, 132, 20));
        $dtaquisicao_raw = trim(substr($primeiraLinha, 152, 11));
        $dehistorico = trim(substr($primeiraLinha, 163, 285));
        
        // Última linha do bloco tem: CDFUNC, CDPROJETO, etc
        $ultimaLinha = explode("\n", $bloco);
        $ultimaLinha = end($ultimaLinha);
        
        $cdfunc = trim(substr($ultimaLinha, 0, 20));
        $cdprojeto = trim(substr($ultimaLinha, 20, 15));
        $nudocfiscal = trim(substr($ultimaLinha, 35, 15));
        $usuario = trim(substr($ultimaLinha, 50, 15));
        $dtoperacao = trim(substr($ultimaLinha, 65, 15));
        $numof = trim(substr($ultimaLinha, 80, 10));
        $codobjeto = trim(substr($ultimaLinha, 90, 15));
        
        // Limpar <null>
        $situacao = ($situacao === '<null>' || $situacao === '') ? 'EM USO' : $situacao;
        $marca = ($marca === '<null>') ? '' : $marca;
        $cor = ($cor === '<null>') ? '' : $cor;
        $usuario = ($usuario === '<null>' || $usuario === '') ? 'SISTEMA' : trim($usuario);
        $dehistorico = ($dehistorico === '<null>') ? '' : $dehistorico;
        
        $cdlocal = is_numeric($cdlocal) ? (int)$cdlocal : 1;
        $cdfunc = is_numeric($cdfunc) ? (int)$cdfunc : null;
        $cdprojeto = is_numeric($cdprojeto) ? (int)$cdprojeto : null;
        $codobjeto = is_numeric($codobjeto) ? (int)$codobjeto : null;
        $numof = is_numeric($numof) ? (int)$numof : null;
        
        // Converter data
        $dtaquisicao = null;
        if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dtaquisicao_raw, $m)) {
            $dtaquisicao = "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        
        $dtoperacao = null;
        if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dtoperacao, $m)) {
            $dtoperacao = "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        
        // Checar se existe
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
                $dehistorico, $situacao, $marca, $modelo, $cor,
                $cdlocal, $cdfunc, $cdprojeto, $codobjeto,
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
                $nupatrimonio, $dehistorico, $situacao, $marca, $modelo, $cor,
                $cdlocal, $cdfunc, $cdprojeto, $codobjeto, $usuario, $dtaquisicao
            ]);
            $novos++;
        }
        
        // Feedback a cada 500
        $processados = $atualizados + $novos;
        if ($processados > 0 && $processados % 500 == 0) {
            echo "📊 $processados processados (atualizados: $atualizados, novos: $novos)\n";
            flush();
        }
        
    } catch (PDOException $e) {
        $erros++;
        if ($erros <= 5) {
            echo "⚠️ Erro #$nupatrimonio: " . substr($e->getMessage(), 0, 80) . "\n";
        }
    }
}

// Resultado
echo "\n════════════════════════════════════════════════════════════\n";
echo "✅ IMPORTAÇÃO CONCLUÍDA!\n";
echo "════════════════════════════════════════════════════════════\n";
echo "📊 Atualizados: $atualizados\n";
echo "📊 Novos: $novos\n";
echo "📊 Erros: $erros\n\n";

// Verificar alguns registros
echo "🔍 Verificando patrimônios #5243, #33074, #16216:\n";
$verificar = [5243, 33074, 16216, 5640];
foreach ($verificar as $num) {
    $stmt = $pdo->query("SELECT NUPATRIMONIO, DEPATRIMONIO, SITUACAO, MARCA, CDLOCAL, USUARIO FROM patr WHERE NUPATRIMONIO = $num");
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        echo "  #$num: DESC='" . substr($r['DEPATRIMONIO'], 0, 30) . "' SIT={$r['SITUACAO']} LOCAL={$r['CDLOCAL']}\n";
    } else {
        echo "  #$num: NÃO ENCONTRADO\n";
    }
}

echo "\n✅ Finalizado!\n";
