<?php
/**
 * Script de importação de LOCAIS DE PROJETO
 * 
 * Este script:
 * 1. Lê o arquivo LocalProjeto.TXT com a NOVA estrutura
 * 2. Vincula locais aos projetos corretos (via CDFANTASIA)
 * 3. Usa updateOrCreate para não duplicar registros
 * 4. Preserva dados existentes e adiciona novos
 * 
 * ESTRUTURA DO ARQUIVO:
 * NUSEQLOCALPROJ | CDLOCAL | DELOCAL | CDFANTASIA | FLATIVO
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LocalProjeto;
use App\Models\Tabfant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║       IMPORTAÇÃO DE LOCAIS DE PROJETO (ATUALIZAÇÃO)        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "Data: " . now()->format('d/m/Y H:i:s') . "\n\n";

// Detectar arquivo
$arquivoPath = null;

if ($argc > 1) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--arquivo=') === 0) {
            $arquivoPath = substr($arg, strlen('--arquivo='));
            echo "📌 Usando arquivo do argumento: $arquivoPath\n\n";
            break;
        }
    }
}

if (!$arquivoPath) {
    $arquivoPath = __DIR__ . '/../storage/imports/Novo import/LocalProjeto.TXT';
    echo "📌 Usando arquivo padrão: $arquivoPath\n\n";
}

if (!file_exists($arquivoPath)) {
    die("❌ ERRO: Arquivo não encontrado: $arquivoPath\n");
}

echo "📄 Arquivo encontrado: $arquivoPath\n";
echo "📊 Analisando arquivo...\n\n";

// Ler arquivo
$conteudo = file_get_contents($arquivoPath);

// Detectar e converter encoding
$encoding = mb_detect_encoding($conteudo, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
if ($encoding && $encoding !== 'UTF-8') {
    echo "🔄 Convertendo encoding de $encoding para UTF-8...\n";
    $conteudo = mb_convert_encoding($conteudo, 'UTF-8', $encoding);
}

$linhas = explode("\n", $conteudo);

// Identificar cabeçalho
$cabecalhoIdx = -1;
$separadorIdx = -1;
foreach ($linhas as $idx => $linha) {
    if (strpos($linha, 'NUSEQLOCALPROJ') !== false && strpos($linha, 'CDLOCAL') !== false) {
        $cabecalhoIdx = $idx;
    }
    if (strpos($linha, '========') !== false && $cabecalhoIdx >= 0) {
        $separadorIdx = $idx;
        break;
    }
}

if ($cabecalhoIdx < 0 || $separadorIdx < 0) {
    die("❌ ERRO: Não foi possível identificar cabeçalho no arquivo\n");
}

echo "✓ Cabeçalho identificado na linha $cabecalhoIdx\n";
echo "✓ Separador na linha $separadorIdx\n\n";

// Extrair posições das colunas
$linhaCabecalho = $linhas[$cabecalhoIdx];
$colunas = ['NUSEQLOCALPROJ', 'CDLOCAL', 'DELOCAL', 'CDFANTASIA', 'FLATIVO'];

$posicoes = [];
foreach ($colunas as $col) {
    $pos = strpos($linhaCabecalho, $col);
    if ($pos !== false) {
        $posicoes[$col] = $pos;
    }
}

echo "✓ Colunas identificadas: " . count($posicoes) . "\n";
echo "  Colunas: " . implode(', ', array_keys($posicoes)) . "\n\n";

// Função para extrair valor
function extrairValor($linha, $coluna, $proximaColuna, $posicoes) {
    if (!isset($posicoes[$coluna])) return null;
    
    $inicio = $posicoes[$coluna];
    $fim = strlen($linha);
    
    if ($proximaColuna && isset($posicoes[$proximaColuna])) {
        $fim = $posicoes[$proximaColuna];
    }
    
    $valor = substr($linha, $inicio, $fim - $inicio);
    $valor = trim($valor);
    
    if ($valor === '<null>' || $valor === '' || $valor === 'NULL') {
        return null;
    }
    
    return $valor;
}

// Carregar mapa de projetos (CDFANTASIA do arquivo -> tabfant_id do banco)
// No banco, a coluna é CDPROJETO, não CDFANTASIA
echo "🔍 Carregando projetos...\n";
$projetosMap = [];
$projetos = Tabfant::whereNotNull('CDPROJETO')->get();
foreach ($projetos as $p) {
    // Mapear CDPROJETO do banco como chave (pois CDFANTASIA do arquivo corresponde a CDPROJETO)
    $projetosMap[$p->CDPROJETO] = $p->id;
}
echo "✓ Projetos carregados: " . count($projetosMap) . "\n\n";

// Processar linhas
$locaisParaProcessar = [];
$avisos = [];
$colunasOrdenadas = array_keys($posicoes);

echo "📦 Processando registros...\n";

for ($i = $separadorIdx + 1; $i < count($linhas); $i++) {
    $linha = $linhas[$i];
    
    if (trim($linha) === '') continue;
    
    $dados = [];
    
    // Extrair colunas
    for ($j = 0; $j < count($colunasOrdenadas); $j++) {
        $coluna = $colunasOrdenadas[$j];
        $proximaColuna = ($j < count($colunasOrdenadas) - 1) ? $colunasOrdenadas[$j + 1] : null;
        $dados[$coluna] = extrairValor($linha, $coluna, $proximaColuna, $posicoes);
    }
    
    // Validações
    $cdlocal = $dados['CDLOCAL'] ?? null;
    $delocal = $dados['DELOCAL'] ?? null;
    $cdfantasia = $dados['CDFANTASIA'] ?? null;
    
    if (empty($cdlocal) || empty($delocal)) {
        continue; // Pular registros incompletos
    }
    
    // Buscar tabfant_id
    $tabfant_id = null;
    if ($cdfantasia && isset($projetosMap[$cdfantasia])) {
        $tabfant_id = $projetosMap[$cdfantasia];
    } else {
        $avisos[] = "Local #$cdlocal ($delocal): Projeto CDFANTASIA=$cdfantasia não encontrado";
    }
    
    $locaisParaProcessar[] = [
        'cdlocal' => (int)$cdlocal,
        'delocal' => strtoupper($delocal),
        'tabfant_id' => $tabfant_id,
        'flativo' => ($dados['FLATIVO'] === '1' || strtoupper($dados['FLATIVO']) === 'S') ? 1 : 0,
    ];
}

$totalParaProcessar = count($locaisParaProcessar);
echo "\n✓ Registros processados: $totalParaProcessar\n";
echo "⚠️  Avisos: " . count($avisos) . "\n\n";

if (count($avisos) > 0 && count($avisos) <= 20) {
    echo "Avisos:\n";
    foreach (array_slice($avisos, 0, 20) as $aviso) {
        echo "  - $aviso\n";
    }
    echo "\n";
}

echo "⚠️  Serão processados $totalParaProcessar locais\n";
echo "   - Novos registros serão ADICIONADOS\n";
echo "   - Registros existentes serão ATUALIZADOS\n\n";

echo "Deseja continuar? (Pressione CTRL+C para cancelar, Enter para continuar)\n";
// fgets(STDIN);

echo "\n🚀 Iniciando importação...\n";

DB::beginTransaction();

try {
    $criados = 0;
    $atualizados = 0;
    $erros = [];
    
    foreach ($locaisParaProcessar as $dados) {
        try {
            $existe = LocalProjeto::where('cdlocal', $dados['cdlocal'])->exists();
            
            LocalProjeto::updateOrCreate(
                ['cdlocal' => $dados['cdlocal']], // Chave
                $dados // Dados
            );
            
            if ($existe) {
                $atualizados++;
            } else {
                $criados++;
            }
            
            if (($criados + $atualizados) % 100 == 0) {
                echo "  Processados: " . ($criados + $atualizados) . "/$totalParaProcessar\n";
            }
        } catch (Exception $e) {
            $erros[] = "Local cdlocal={$dados['cdlocal']}: " . $e->getMessage();
        }
    }
    
    DB::commit();
    
    echo "\n✅ IMPORTAÇÃO CONCLUÍDA!\n\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║                      RESUMO FINAL                          ║\n";
    echo "╠════════════════════════════════════════════════════════════╣\n";
    echo "║  Total processado:       " . str_pad($totalParaProcessar, 6, ' ', STR_PAD_LEFT) . "                         ║\n";
    echo "║  Novos criados:          " . str_pad($criados, 6, ' ', STR_PAD_LEFT) . "                         ║\n";
    echo "║  Atualizados:            " . str_pad($atualizados, 6, ' ', STR_PAD_LEFT) . "                         ║\n";
    echo "║  Erros:                  " . str_pad(count($erros), 6, ' ', STR_PAD_LEFT) . "                         ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    if (count($erros) > 0) {
        echo "❌ Erros:\n";
        foreach (array_slice($erros, 0, 10) as $erro) {
            echo "  - $erro\n";
        }
        if (count($erros) > 10) {
            echo "  ... e mais " . (count($erros) - 10) . " erros\n";
        }
    }
    
    Log::info('Importação de locais concluída', [
        'total' => $totalParaProcessar,
        'criados' => $criados,
        'atualizados' => $atualizados,
        'erros' => count($erros)
    ]);
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERRO CRÍTICO:\n";
    echo $e->getMessage() . "\n";
    echo "\nTransação revertida.\n";
    
    Log::error('Falha na importação de locais', [
        'erro' => $e->getMessage()
    ]);
    
    exit(1);
}

echo "\n✅ Script finalizado!\n";
