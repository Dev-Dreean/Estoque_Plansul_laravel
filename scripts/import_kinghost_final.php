<?php
/**
 * IMPORTAÇÃO FINAL KINGHOST - Versão igual ao local que funcionou
 * Baseado no import_patrimonio_completo.php que usou Laravel localmente
 * Adaptado para PDO puro para rodar no KingHost
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('max_execution_time', 0);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  IMPORTAÇÃO KINGHOST - VERSÃO LOCAL ADAPTADA              ║\n";
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
    echo "✅ Conectado ao banco de dados\n\n";
} catch (PDOException $e) {
    die("❌ Erro de conexão: " . $e->getMessage() . "\n");
}

// ============================================================================
// ETAPA 1: IMPORTAR LOCAIS
// ============================================================================
echo "🏗️  ETAPA 1: IMPORTANDO LOCAIS\n";
echo "════════════════════════════════════════════════════════════\n";

$arquivo_locais = __DIR__ . '/../LocalProjeto_NOVO.TXT';
if (file_exists($arquivo_locais)) {
    $linhas = file($arquivo_locais, FILE_IGNORE_NEW_LINES);
    $locais_novos = 0;
    $locais_pulados = 0;
    
    for ($i = 2; $i < count($linhas); $i++) {
        $linha = $linhas[$i];
        if (strlen(trim($linha)) < 10) continue;
        
        if (!mb_check_encoding($linha, 'UTF-8')) {
            $linha = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $linha);
        }
        
        $cdlocal = trim(substr($linha, 0, 11));
        $cdprojeto_str = trim(substr($linha, 11, 13));
        $delocal = trim(substr($linha, 24, 256));
        
        if (!is_numeric($cdlocal)) continue;
        $cdlocal = (int)$cdlocal;
        $cdprojeto = is_numeric($cdprojeto_str) ? (int)$cdprojeto_str : null;
        
        // Verificar se existe
        $check = $pdo->prepare("SELECT id FROM locais_projeto WHERE cdlocal = ? AND codigo_projeto = ? LIMIT 1");
        $check->execute([$cdlocal, $cdprojeto]);
        
        if (!$check->fetch()) {
            try {
                $insert = $pdo->prepare("INSERT INTO locais_projeto (cdlocal, codigo_projeto, delocal, flativo) VALUES (?, ?, ?, 1)");
                $insert->execute([$cdlocal, $cdprojeto, $delocal]);
                $locais_novos++;
            } catch (Exception $e) {
                $locais_pulados++;
            }
        } else {
            $locais_pulados++;
        }
    }
    
    echo "✅ Locais: $locais_novos novos + $locais_pulados existentes\n\n";
} else {
    echo "⚠️  Arquivo de locais não encontrado\n\n";
}

// ============================================================================
// ETAPA 2: IMPORTAR PATRIMÔNIOS
// ============================================================================
echo "🏛️  ETAPA 2: IMPORTANDO PATRIMÔNIOS\n";
echo "════════════════════════════════════════════════════════════\n";

$arquivo = __DIR__ . '/../Patrimonio_NOVO.TXT';
if (!file_exists($arquivo)) {
    die("❌ Arquivo não encontrado: $arquivo\n");
}

// LER como conteúdo completo (pois tem quebras de linha dentro de registros)
$conteudo = file_get_contents($arquivo);

if (!mb_check_encoding($conteudo, 'UTF-8')) {
    $conteudo = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $conteudo);
}

echo "📄 Arquivo carregado\n";

// Encontrar posição do separador
$pos_sep = strpos($conteudo, '============    ');
if ($pos_sep === false) {
    die("❌ Separador não encontrado\n");
}

// Dados começam após separador
$dados_raw = substr($conteudo, $pos_sep + 160);

// Dividir em registros (cada registro começa com NUPATRIMONIO)
$registros = [];
$linhas_dados = explode("\n", $dados_raw);

foreach ($linhas_dados as $idx => $linha) {
    $linha_trim = trim($linha);
    
    // Detectar início de novo registro (começa com número e tem vários espaços)
    if (preg_match('/^(\d+)\s{10,}/', $linha_trim)) {
        // É um novo registro - extrair número e posição
        preg_match('/^(\d+)/', $linha_trim, $m);
        $nupatrimonio = (int)$m[1];
        
        // Concatenar próximas linhas até achar o próximo número (ou fim)
        $bloco = $linha;
        $j = $idx + 1;
        while ($j < count($linhas_dados)) {
            $prox = $linhas_dados[$j];
            if (preg_match('/^(\d+)\s{10,}/', trim($prox)) && $j != $idx + 1) {
                break; // Encontrou próximo registro
            }
            $bloco .= "\n" . $prox;
            $j++;
        }
        
        $registros[] = ['num' => $nupatrimonio, 'bloco' => $bloco];
    }
}

$total = count($registros);
echo "📊 Registros detectados: $total\n";
echo "⏳ Processando...\n\n";

$importados = 0;
$atualizados = 0;
$erros_lista = [];

foreach ($registros as $reg) {
    try {
        $nupatrimonio = $reg['num'];
        $bloco = $reg['bloco'];
        $linhas_bloco = explode("\n", $bloco);
        
        // Primeira linha tem maioria dos dados
        $linha1 = $linhas_bloco[0];
        $situacao = trim(substr($linha1, 16, 35));
        $marca = trim(substr($linha1, 51, 35));
        $cdlocal = trim(substr($linha1, 86, 11));
        $modelo = trim(substr($linha1, 97, 35));
        $cor = trim(substr($linha1, 132, 20));
        $dtaquisicao_raw = trim(substr($linha1, 152, 11));
        $depatrimonio = trim(substr($linha1, 163, 285));
        
        // Última linha tem dados finais
        $ultima = end($linhas_bloco);
        
        // Extrair campos da última linha (posições diferentes)
        $cdfunc = trim(substr($ultima, 0, 20));
        $cdprojeto = trim(substr($ultima, 20, 15));
        $usuario = trim(substr($ultima, 50, 15));
        $codobjeto = trim(substr($ultima, 90, 15));
        
        // Limpar valores
        $situacao = ($situacao === '<null>' || $situacao === '') ? 'EM USO' : $situacao;
        $marca = ($marca === '<null>') ? '' : $marca;
        $cor = ($cor === '<null>') ? '' : $cor;
        $depatrimonio = ($depatrimonio === '<null>') ? '' : $depatrimonio;
        $usuario = ($usuario === '<null>' || trim($usuario) === '') ? 'SISTEMA' : trim($usuario);
        
        // Validar números
        $cdlocal = is_numeric($cdlocal) ? (int)$cdlocal : 1;
        $cdfunc = is_numeric($cdfunc) ? (int)$cdfunc : 133838;
        $cdprojeto = is_numeric($cdprojeto) ? (int)$cdprojeto : 8;
        $codobjeto = is_numeric($codobjeto) ? (int)$codobjeto : null;
        
        // Converter data
        $dtaquisicao = null;
        if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dtaquisicao_raw, $m)) {
            $dtaquisicao = "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        
        // Verificar se existe
        $check = $pdo->prepare("SELECT NUSEQPATR FROM patr WHERE NUPATRIMONIO = ? LIMIT 1");
        $check->execute([$nupatrimonio]);
        $existe = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($existe) {
            // UPDATE
            $sql = "UPDATE patr SET 
                DEPATRIMONIO = ?, SITUACAO = ?, MARCA = ?, MODELO = ?, COR = ?,
                CDLOCAL = ?, CDMATRFUNCIONARIO = ?, CDPROJETO = ?, CODOBJETO = ?, USUARIO = ?,
                DTAQUISICAO = ?
                WHERE NUPATRIMONIO = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $depatrimonio, $situacao, $marca, $modelo, $cor,
                $cdlocal, $cdfunc, $cdprojeto, $codobjeto, $usuario,
                $dtaquisicao, $nupatrimonio
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
                $cdlocal, $cdfunc, $cdprojeto, $codobjeto, $usuario, $dtaquisicao
            ]);
            
            $importados++;
        }
        
        // Feedback a cada 500
        $processados = $importados + $atualizados;
        if ($processados > 0 && $processados % 500 == 0) {
            echo "  📊 $processados processados (novos: $importados, atualizados: $atualizados)\n";
            flush();
        }
        
    } catch (PDOException $e) {
        if (count($erros_lista) < 10) {
            $erros_lista[] = "Patrimônio #$nupatrimonio: " . substr($e->getMessage(), 0, 80);
        }
    }
}

echo "\n";
echo "════════════════════════════════════════════════════════════\n";
echo "✅ IMPORTAÇÃO CONCLUÍDA!\n";
echo "════════════════════════════════════════════════════════════\n";
echo "📊 Patrimônios importados: $importados\n";
echo "📊 Patrimônios atualizados: $atualizados\n";
echo "📊 Total processado: " . ($importados + $atualizados) . "\n";
echo "⚠️  Erros: " . count($erros_lista) . "\n\n";

if (!empty($erros_lista)) {
    echo "Primeiros erros:\n";
    foreach ($erros_lista as $erro) {
        echo "  - $erro\n";
    }
}

// Verificações finais
echo "\n🔍 Verificação final dos registros:\n";
$total_patr = $pdo->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "  - Total de patrimônios no banco: $total_patr\n";

$verificar = [5243, 33074, 16216, 5640, 3, 38, 45];
echo "\n  Amostra de registros:\n";
foreach ($verificar as $num) {
    $stmt = $pdo->query("SELECT NUPATRIMONIO, DEPATRIMONIO, SITUACAO, MARCA, CDLOCAL, USUARIO FROM patr WHERE NUPATRIMONIO = $num LIMIT 1");
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        $desc_show = strlen($r['DEPATRIMONIO']) > 40 ? substr($r['DEPATRIMONIO'], 0, 40) . '...' : $r['DEPATRIMONIO'];
        echo "  ✅ #$num: {$r['SITUACAO']} | LOCAL {$r['CDLOCAL']} | USER: {$r['USUARIO']}\n";
    } else {
        echo "  ❌ #$num: NÃO ENCONTRADO\n";
    }
}

echo "\n✅ Finalizado com sucesso!\n";
