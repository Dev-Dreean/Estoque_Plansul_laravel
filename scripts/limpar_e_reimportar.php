<?php
/**
 * LIMPAR BANCO E REIMPORTAR DO ZERO
 * DELETAR TODOS OS PATRIMÔNIOS E IMPORTAR APENAS DO ARQUIVO NOVO
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  LIMPEZA E REIMPORTAÇÃO COMPLETA                          ║\n";
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
    die("❌ Erro: " . $e->getMessage() . "\n");
}

// PASSO 1: DELETAR TODOS OS PATRIMÔNIOS
echo "🗑️  PASSO 1: DELETANDO TODOS OS PATRIMÔNIOS EXISTENTES\n";
echo "════════════════════════════════════════════════════════════\n";

$count_antes = $pdo->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "Total antes: $count_antes\n";

$pdo->exec("DELETE FROM patr");
echo "✅ TODOS os patrimônios deletados\n\n";

// PASSO 2: DELETAR TODOS OS LOCAIS
echo "🗑️  PASSO 2: DELETANDO TODOS OS LOCAIS\n";
echo "════════════════════════════════════════════════════════════\n";

$count_locais = $pdo->query("SELECT COUNT(*) as cnt FROM locais_projeto")->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "Total locais antes: $count_locais\n";

$pdo->exec("DELETE FROM locais_projeto");
echo "✅ TODOS os locais deletados\n\n";

// PASSO 3: IMPORTAR LOCAIS DO ARQUIVO NOVO
echo "🏗️  PASSO 3: IMPORTANDO LOCAIS\n";
echo "════════════════════════════════════════════════════════════\n";

$arquivo_locais = __DIR__ . '/../LocalProjeto_NOVO.TXT';
if (file_exists($arquivo_locais)) {
    $linhas = file($arquivo_locais, FILE_IGNORE_NEW_LINES);
    $locais_novos = 0;
    
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
        
        try {
            $insert = $pdo->prepare("INSERT INTO locais_projeto (cdlocal, codigo_projeto, delocal, flativo) VALUES (?, ?, ?, 1)");
            $insert->execute([$cdlocal, $cdprojeto, $delocal]);
            $locais_novos++;
        } catch (Exception $e) {
            // Ignorar duplicatas
        }
    }
    
    echo "✅ $locais_novos locais importados\n\n";
}

// PASSO 4: IMPORTAR PATRIMÔNIOS DO ARQUIVO NOVO
echo "🏛️  PASSO 4: IMPORTANDO PATRIMÔNIOS (APENAS INSERTS)\n";
echo "════════════════════════════════════════════════════════════\n";

$arquivo = __DIR__ . '/../Patrimonio_NOVO.TXT';
if (!file_exists($arquivo)) {
    die("❌ Arquivo não encontrado\n");
}

$conteudo = file_get_contents($arquivo);
if (!mb_check_encoding($conteudo, 'UTF-8')) {
    $conteudo = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $conteudo);
}

// Encontrar separador
$pos_sep = strpos($conteudo, '============    ');
if ($pos_sep === false) {
    die("❌ Separador não encontrado\n");
}

$dados_raw = substr($conteudo, $pos_sep + 160);
$linhas_dados = explode("\n", $dados_raw);

// Detectar registros
$registros = [];
foreach ($linhas_dados as $idx => $linha) {
    $linha_trim = trim($linha);
    
    if (preg_match('/^(\d+)\s{10,}/', $linha_trim)) {
        preg_match('/^(\d+)/', $linha_trim, $m);
        $nupatrimonio = (int)$m[1];
        
        $bloco = $linha;
        $j = $idx + 1;
        while ($j < count($linhas_dados)) {
            $prox = $linhas_dados[$j];
            if (preg_match('/^(\d+)\s{10,}/', trim($prox)) && $j != $idx + 1) {
                break;
            }
            $bloco .= "\n" . $prox;
            $j++;
        }
        
        $registros[] = ['num' => $nupatrimonio, 'bloco' => $bloco];
    }
}

$total = count($registros);
echo "📊 Registros: $total\n";
echo "⏳ Importando...\n\n";

$importados = 0;
$erros = 0;

foreach ($registros as $reg) {
    try {
        $nupatrimonio = $reg['num'];
        $bloco = $reg['bloco'];
        $linhas_bloco = explode("\n", $bloco);
        
        // LINHA 1: NUPATRIMONIO SITUACAO MARCA CDLOCAL MODELO
        $linha1 = $linhas_bloco[0];
        $situacao = trim(substr($linha1, 16, 35));
        $marca = trim(substr($linha1, 51, 35));
        $cdlocal = trim(substr($linha1, 86, 11));
        $modelo = trim(substr($linha1, 97, 35));
        
        // LINHA 2: COR DTAQUISICAO DEPATRIMONIO
        $linha2 = isset($linhas_bloco[1]) ? $linhas_bloco[1] : '';
        $cor = trim(substr($linha2, 0, 20));
        $dtaquisicao_raw = trim(substr($linha2, 20, 15));
        $depatrimonio = trim(substr($linha2, 35, 280));
        
        // LINHA 5 (última): CDFUNC CDPROJETO NUDOCFISCAL USUARIO DTOPERACAO NUMOF CODOBJETO
        $ultima = end($linhas_bloco);
        $cdfunc = trim(substr($ultima, 0, 20));
        $cdprojeto = trim(substr($ultima, 20, 13));
        $nudocfiscal = trim(substr($ultima, 33, 15));
        $usuario = trim(substr($ultima, 48, 15));
        $dtoperacao_raw = trim(substr($ultima, 63, 15));
        $numof = trim(substr($ultima, 78, 10));
        $codobjeto = trim(substr($ultima, 88, 13));
        
        // Limpar
        $situacao = ($situacao === '<null>' || $situacao === '') ? 'EM USO' : $situacao;
        $marca = ($marca === '<null>') ? '' : $marca;
        $cor = ($cor === '<null>') ? '' : $cor;
        $depatrimonio = ($depatrimonio === '<null>') ? '' : $depatrimonio;
        $usuario = ($usuario === '<null>' || trim($usuario) === '') ? 'SISTEMA' : trim($usuario);
        
        $cdlocal = is_numeric($cdlocal) ? (int)$cdlocal : 1;
        $cdfunc = is_numeric($cdfunc) ? (int)$cdfunc : 133838;
        $cdprojeto = is_numeric($cdprojeto) ? (int)$cdprojeto : 8;
        $codobjeto = is_numeric($codobjeto) ? (int)$codobjeto : null;
        
        $dtaquisicao = null;
        if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dtaquisicao_raw, $m)) {
            $dtaquisicao = "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        
        // APENAS INSERT (sem verificar se existe)
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
        
        if ($importados % 500 == 0) {
            echo "  📊 $importados importados\n";
            flush();
        }
        
    } catch (PDOException $e) {
        $erros++;
        if ($erros <= 5) {
            echo "⚠️ Erro #$nupatrimonio: " . substr($e->getMessage(), 0, 80) . "\n";
        }
    }
}

echo "\n════════════════════════════════════════════════════════════\n";
echo "✅ REIMPORTAÇÃO COMPLETA!\n";
echo "════════════════════════════════════════════════════════════\n";
echo "📊 Patrimônios importados: $importados\n";
echo "⚠️  Erros: $erros\n\n";

// Verificar total final
$total_final = $pdo->query("SELECT COUNT(*) as cnt FROM patr")->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "✅ Total final no banco: $total_final\n";
echo "   (deve ser igual ao local: ~11.381)\n\n";

echo "✅ CONCLUÍDO!\n";
