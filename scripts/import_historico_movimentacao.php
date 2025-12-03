<?php
/**
 * Script de importação de HISTÓRICO DE MOVIMENTAÇÕES
 * 
 * Este script:
 * 1. Lê o arquivo Hist_movpatr.TXT
 * 2. Importa registros de movimentações preservando usuários
 * 3. Vincula corretamente com patrimônios e projetos
 * 4. Usa updateOrCreate para evitar duplicatas
 * 
 * ESTRUTURA DO ARQUIVO:
 * NUPATRIM | NUPROJ | DTMOVI | FLMOV | USUARIO | DTOPERACAO
 * 
 * MAPEAMENTO PARA TABELA movpartr:
 * - NUPATR (NUPATRIM do arquivo)
 * - CODPROJ (NUPROJ do arquivo)
 * - DTOPERACAO (DTMOVI ou DTOPERACAO do arquivo)
 * - USUARIO (preservado)
 * - TIPO (derivado de FLMOV: I=inclusão, A=alteração, etc.)
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\HistoricoMovimentacao;
use App\Models\Patrimonio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     IMPORTAÇÃO DE HISTÓRICO DE MOVIMENTAÇÕES (UPDATE)      ║\n";
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
    $arquivoPath = __DIR__ . '/../storage/imports/Novo import/Hist_movpatr.TXT';
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
    if (strpos($linha, 'NUPATRIM') !== false && strpos($linha, 'NUPROJ') !== false) {
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
$colunas = ['NUPATRIM', 'NUPROJ', 'DTMOVI', 'FLMOV', 'USUARIO', 'DTOPERACAO'];

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

// Carregar mapa de usuários
echo "🔍 Carregando usuários...\n";
$usuariosMap = [];
$usuarios = User::all();
foreach ($usuarios as $u) {
    if ($u->NMLOGIN) {
        $usuariosMap[strtoupper(trim($u->NMLOGIN))] = $u;
    }
}
echo "✓ Usuários carregados: " . count($usuariosMap) . "\n\n";

// Carregar mapa de patrimônios (para validação)
echo "🔍 Carregando patrimônios...\n";
$patrimoniosMap = [];
$patrimonios = Patrimonio::select('NUPATRIMONIO', 'NUSEQPATR')->get();
foreach ($patrimonios as $p) {
    $patrimoniosMap[$p->NUPATRIMONIO] = $p->NUSEQPATR;
}
echo "✓ Patrimônios carregados: " . count($patrimoniosMap) . "\n\n";

// Processar linhas
$movimentacoesParaProcessar = [];
$avisos = [];
$colunasOrdenadas = array_keys($posicoes);

echo "📦 Processando registros...\n";

// Função para normalizar data
$normalizarData = function($dataStr) {
    if (empty($dataStr)) return null;
    
    // dd/mm/yyyy
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dataStr, $matches)) {
        return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
    }
    
    return null;
};

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
    
    // Validar dados obrigatórios
    $nupatrim = $dados['NUPATRIM'] ?? null;
    $nuproj = $dados['NUPROJ'] ?? null;
    $usuario = $dados['USUARIO'] ?? 'SISTEMA';
    
    // Se NUPATRIM é 0 ou vazio, pular (registro inválido)
    if (empty($nupatrim) || $nupatrim === '0') {
        continue;
    }
    
    $nupatrim = (int)$nupatrim;
    $nuproj = $nuproj ? (int)$nuproj : null;
    
    // Validar usuário
    $usuarioUpper = strtoupper(trim($usuario));
    if (!isset($usuariosMap[$usuarioUpper])) {
        // Usuário não existe - usar SISTEMA
        $avisos[] = "NUPATRIM=$nupatrim: Usuário '$usuario' não encontrado, usando 'SISTEMA'";
        $usuario = 'SISTEMA';
    }
    
    // Determinar tipo de movimentação baseado em FLMOV
    $flmov = $dados['FLMOV'] ?? 'I';
    $tipo = match(strtoupper($flmov)) {
        'I' => 'INCLUSAO',
        'A' => 'ALTERACAO',
        'E' => 'EXCLUSAO',
        'M' => 'MOVIMENTACAO',
        default => 'MOVIMENTACAO'
    };
    
    // Datas
    $dtmovi = $normalizarData($dados['DTMOVI']) ?: now()->format('Y-m-d');
    $dtoperacao = $normalizarData($dados['DTOPERACAO']) ?: $dtmovi;
    
    $movimentacoesParaProcessar[] = [
        'NUPATR' => $nupatrim,
        'CODPROJ' => $nuproj,
        'USUARIO' => $usuario,
        'DTOPERACAO' => $dtoperacao,
        'TIPO' => $tipo,
        'CAMPO' => 'HISTORICO_IMPORTACAO', // Identificar que veio da importação
        'VALOR_ANTIGO' => null,
        'VALOR_NOVO' => "Movimentação importada em " . now()->format('d/m/Y H:i:s'),
        'CO_AUTOR' => null,
    ];
}

$totalParaProcessar = count($movimentacoesParaProcessar);
echo "\n✓ Registros processados: $totalParaProcessar\n";
echo "⚠️  Avisos: " . count($avisos) . "\n\n";

if (count($avisos) > 0 && count($avisos) <= 20) {
    echo "Avisos:\n";
    foreach (array_slice($avisos, 0, 20) as $aviso) {
        echo "  - $aviso\n";
    }
    echo "\n";
}

echo "⚠️  Serão processados $totalParaProcessar registros de histórico\n";
echo "   - Registros serão ADICIONADOS ao histórico existente\n";
echo "   - Usuários serão preservados conforme arquivo\n\n";

echo "Deseja continuar? (Pressione CTRL+C para cancelar, Enter para continuar)\n";
// fgets(STDIN);

echo "\n🚀 Iniciando importação...\n";

DB::beginTransaction();

try {
    $criados = 0;
    $erros = [];
    
    foreach ($movimentacoesParaProcessar as $dados) {
        try {
            // Criar registro (não usar updateOrCreate para histórico - sempre inserir)
            HistoricoMovimentacao::create($dados);
            
            $criados++;
            
            if ($criados % 500 == 0) {
                echo "  Importados: $criados/$totalParaProcessar\n";
            }
        } catch (Exception $e) {
            $erros[] = "NUPATR={$dados['NUPATR']}: " . $e->getMessage();
            
            // Se muitos erros, abortar
            if (count($erros) > 100) {
                throw new Exception("Muitos erros (>100). Abortando...");
            }
        }
    }
    
    DB::commit();
    
    echo "\n✅ IMPORTAÇÃO CONCLUÍDA!\n\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║                      RESUMO FINAL                          ║\n";
    echo "╠════════════════════════════════════════════════════════════╣\n";
    echo "║  Total processado:       " . str_pad($totalParaProcessar, 6, ' ', STR_PAD_LEFT) . "                         ║\n";
    echo "║  Registros criados:      " . str_pad($criados, 6, ' ', STR_PAD_LEFT) . "                         ║\n";
    echo "║  Erros:                  " . str_pad(count($erros), 6, ' ', STR_PAD_LEFT) . "                         ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    if (count($erros) > 0) {
        echo "❌ Erros:\n";
        foreach (array_slice($erros, 0, 20) as $erro) {
            echo "  - $erro\n";
        }
        if (count($erros) > 20) {
            echo "  ... e mais " . (count($erros) - 20) . " erros\n";
        }
    }
    
    Log::info('Importação de histórico concluída', [
        'total' => $totalParaProcessar,
        'criados' => $criados,
        'erros' => count($erros)
    ]);
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERRO CRÍTICO:\n";
    echo $e->getMessage() . "\n";
    echo "\nTransação revertida.\n";
    
    Log::error('Falha na importação de histórico', [
        'erro' => $e->getMessage()
    ]);
    
    exit(1);
}

echo "\n✅ Script finalizado!\n";
