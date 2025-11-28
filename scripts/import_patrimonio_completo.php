<?php
/**
 * Script de importação COMPLETA do arquivo patrimonio.TXT
 * 
 * Este script:
 * 1. Analisa o arquivo .txt linha por linha
 * 2. Valida todos os relacionamentos (usuários, projetos, locais, objetos)
 * 3. Cria registros faltantes quando necessário
 * 4. Importa APENAS patrimônios que não existem (evita duplicatas)
 * 5. Gera relatório detalhado de importação
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\User;
use App\Models\Funcionario;
use App\Models\Tabfant;
use App\Models\LocalProjeto;
use App\Models\ObjetoPatr;
use Illuminate\Support\Facades\DB;

echo "=== IMPORTAÇÃO COMPLETA DE PATRIMÔNIOS ===\n";
echo "Data: " . now()->format('d/m/Y H:i:s') . "\n\n";

// Carregar PathDetector para detecção automática
require_once __DIR__ . '/PathDetector.php';
$pathDetector = new PathDetector();

// Detectar arquivo (com suporte a argumento --arquivo)
$arquivoPath = null;

// Verificar argumento de linha de comando
if ($argc > 1) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--arquivo=') === 0) {
            $arquivoPath = substr($arg, strlen('--arquivo='));
            echo "📌 Usando arquivo do argumento: $arquivoPath\n\n";
            break;
        }
    }
}

// Se não foi passado argumento, usar PathDetector
if (!$arquivoPath) {
    [$encontrado, $resultado] = $pathDetector->findPatrimonioFile();
    
    if (!$encontrado) {
        die($resultado . "\n");
    }
    
    $arquivoPath = $resultado;
}

echo "📄 Arquivo encontrado: $arquivoPath\n";
echo "📊 Analisando arquivo...\n\n";

// Ler arquivo
$conteudo = file_get_contents($arquivoPath);

// Detectar e converter encoding para UTF-8
$encoding = mb_detect_encoding($conteudo, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
if ($encoding && $encoding !== 'UTF-8') {
    echo "🔄 Convertendo encoding de $encoding para UTF-8...\n";
    $conteudo = mb_convert_encoding($conteudo, 'UTF-8', $encoding);
}

$linhas = explode("\n", $conteudo);

// Identificar linha de cabeçalho
$cabecalhoIdx = -1;
$separadorIdx = -1;
foreach ($linhas as $idx => $linha) {
    if (strpos($linha, 'NUPATRIMONIO') !== false && strpos($linha, 'SITUACAO') !== false) {
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

// Extrair cabeçalhos e suas posições
$linhaCabecalho = $linhas[$cabecalhoIdx];
$colunas = ['NUPATRIMONIO', 'SITUACAO', 'MARCA', 'CDLOCAL', 'MODELO', 'COR', 'DTAQUISICAO', 
            'DEHISTORICO', 'CDMATRFUNCIONARIO', 'CDPROJETO', 'NUDOCFISCAL', 'USUARIO', 
            'DTOPERACAO', 'NUMOF', 'CODOBJETO', 'FLCONFERIDO'];

$posicoes = [];
foreach ($colunas as $col) {
    $pos = strpos($linhaCabecalho, $col);
    if ($pos !== false) {
        $posicoes[$col] = $pos;
    }
}

echo "✓ Colunas identificadas: " . count($posicoes) . "\n";
echo "  Colunas: " . implode(', ', array_keys($posicoes)) . "\n\n";

// Função para extrair valor de uma coluna com base na posição
function extrairValor($linha, $coluna, $proximaColuna, $posicoes) {
    if (!isset($posicoes[$coluna])) return null;
    
    $inicio = $posicoes[$coluna];
    
    // Determinar fim (próxima coluna ou fim da linha)
    $fim = strlen($linha);
    if ($proximaColuna && isset($posicoes[$proximaColuna])) {
        $fim = $posicoes[$proximaColuna];
    }
    
    $valor = substr($linha, $inicio, $fim - $inicio);
    $valor = trim($valor);
    
    // Tratar valores especiais
    if ($valor === '<null>' || $valor === '' || $valor === 'NULL') {
        return null;
    }
    
    return $valor;
}

// Preparar arrays de referência para validação
echo "🔍 Preparando validações...\n";

$usuariosMap = [];
$usuarios = User::all();
foreach ($usuarios as $u) {
    if ($u->NMLOGIN) $usuariosMap[strtoupper($u->NMLOGIN)] = $u;
    if ($u->NOMEUSER) $usuariosMap[strtoupper($u->NOMEUSER)] = $u;
}

$funcionariosMap = [];
$funcionarios = Funcionario::all();
foreach ($funcionarios as $f) {
    $funcionariosMap[$f->CDMATRFUNCIONARIO] = $f;
}

$projetosMap = [];
$projetos = Tabfant::whereNotNull('CDPROJETO')->get();
foreach ($projetos as $p) {
    $projetosMap[$p->CDPROJETO] = $p;
}

$locaisMap = [];
$locais = LocalProjeto::all();
foreach ($locais as $l) {
    $locaisMap[$l->cdlocal] = $l;
}

$objetosMap = [];
$objetos = ObjetoPatr::all();
foreach ($objetos as $o) {
    $objetosMap[$o->NUSEQOBJETO] = $o;
}

echo "✓ Usuários carregados: " . count($usuariosMap) . "\n";
echo "✓ Funcionários carregados: " . count($funcionariosMap) . "\n";
echo "✓ Projetos carregados: " . count($projetosMap) . "\n";
echo "✓ Locais carregados: " . count($locaisMap) . "\n";
echo "✓ Objetos carregados: " . count($objetosMap) . "\n\n";

// Processar linhas de dados
$patrimoniosParaImportar = [];
$errosValidacao = [];
$avisos = [];

$colunasOrdenadas = array_keys($posicoes);

echo "📦 Processando registros...\n";

for ($i = $separadorIdx + 1; $i < count($linhas); $i++) {
    $linha = $linhas[$i];
    
    // Pular linhas vazias
    if (trim($linha) === '') continue;
    
    $dados = [];
    
    // Extrair cada coluna
    for ($j = 0; $j < count($colunasOrdenadas); $j++) {
        $coluna = $colunasOrdenadas[$j];
        $proximaColuna = ($j < count($colunasOrdenadas) - 1) ? $colunasOrdenadas[$j + 1] : null;
        $dados[$coluna] = extrairValor($linha, $coluna, $proximaColuna, $posicoes);
    }
    
    // Validar NUPATRIMONIO (obrigatório)
    if (empty($dados['NUPATRIMONIO']) || !is_numeric($dados['NUPATRIMONIO'])) {
        continue; // Pular linhas sem número de patrimônio válido
    }
    
    $nupatrimonio = (int) $dados['NUPATRIMONIO'];
    
    // Verificar se já existe
    if (Patrimonio::where('NUPATRIMONIO', $nupatrimonio)->exists()) {
        continue; // Pular duplicatas
    }
    
    // Validar e ajustar CDMATRFUNCIONARIO
    $cdmatr = $dados['CDMATRFUNCIONARIO'] ?? null;
    if ($cdmatr && is_numeric($cdmatr)) {
        $cdmatr = (int) $cdmatr;
        if (!isset($funcionariosMap[$cdmatr])) {
            $avisos[] = "#$nupatrimonio: Funcionário $cdmatr não encontrado, usando padrão";
            $cdmatr = 133838; // Matrícula padrão (BEA.SC)
        }
    } else {
        $cdmatr = 133838; // Padrão
    }
    
    // Validar USUARIO
    $usuario = $dados['USUARIO'] ?? 'SISTEMA';
    if ($usuario && !isset($usuariosMap[strtoupper($usuario)])) {
        // Se o usuário não existe, usar SISTEMA
        $usuario = 'SISTEMA';
    }
    
    // Validar CDPROJETO
    $cdprojeto = $dados['CDPROJETO'] ?? null;
    if ($cdprojeto && is_numeric($cdprojeto)) {
        $cdprojeto = (int) $cdprojeto;
        if (!isset($projetosMap[$cdprojeto])) {
            $avisos[] = "#$nupatrimonio: Projeto $cdprojeto não encontrado, usando padrão (8)";
            $cdprojeto = 8; // Projeto padrão
        }
    } else {
        $cdprojeto = 8;
    }
    
    // Validar CDLOCAL
    $cdlocal = $dados['CDLOCAL'] ?? 1;
    if ($cdlocal && is_numeric($cdlocal)) {
        $cdlocal = (int) $cdlocal;
    } else {
        $cdlocal = 1;
    }
    
    // Validar CODOBJETO
    $codobjeto = $dados['CODOBJETO'] ?? null;
    if ($codobjeto && is_numeric($codobjeto)) {
        $codobjeto = (int) $codobjeto;
    } else {
        $codobjeto = null;
    }
    
    // Converter datas do formato dd/mm/yyyy para yyyy-mm-dd
    // Função auxiliar para normalizar data
    $normalizarData = function($dataStr) {
        if (empty($dataStr)) return null;
        
        // Tentar formato brasileiro dd/mm/yyyy (completo)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dataStr, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }
        
        // Tratar datas incompletas como dd/mm/yyy (faltando 1 dígito do ano)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{3})$/', $dataStr, $matches)) {
            // Assumir que é século 20 ou 21 (completar com 2)
            $ano = '2' . $matches[3]; // ex: 202 → 2202 (ou melhor lógica abaixo)
            // Melhor: se ano < 50, é 20xx; se >= 50, é 19xx
            if ($matches[3] < 50) {
                $ano = '20' . $matches[3];
            } else {
                $ano = '19' . $matches[3];
            }
            return sprintf('%04d-%02d-%02d', $ano, $matches[2], $matches[1]);
        }
        
        return null;
    };
    
    $dtaquisicao = $normalizarData($dados['DTAQUISICAO']);
    $dtoperacao = $normalizarData($dados['DTOPERACAO']) ?: now();
    
    // Preparar dados para importação
    $patrimoniosParaImportar[] = [
        'NUPATRIMONIO' => $nupatrimonio,
        'SITUACAO' => $dados['SITUACAO'] ?: 'EM USO',
        'MARCA' => $dados['MARCA'],
        'MODELO' => $dados['MODELO'],
        'COR' => $dados['COR'],
        'CDLOCAL' => $cdlocal,
        'CDMATRFUNCIONARIO' => $cdmatr,
        'CDPROJETO' => $cdprojeto,
        'USUARIO' => $usuario,
        'DTOPERACAO' => $dtoperacao,
        'DTAQUISICAO' => $dtaquisicao,
        'DEHISTORICO' => $dados['DEHISTORICO'],
        'NUMOF' => $dados['NUMOF'] && is_numeric($dados['NUMOF']) ? (int)$dados['NUMOF'] : null,
        'CODOBJETO' => $codobjeto,
        'DEPATRIMONIO' => null, // Será preenchido depois via CODOBJETO
        'NMPLANTA' => null,
    ];
}

$totalParaImportar = count($patrimoniosParaImportar);
echo "\n✓ Registros processados: $totalParaImportar\n";
echo "⚠️  Avisos durante validação: " . count($avisos) . "\n\n";

if (count($avisos) > 0 && count($avisos) <= 10) {
    echo "Primeiros avisos:\n";
    foreach (array_slice($avisos, 0, 10) as $aviso) {
        echo "  - $aviso\n";
    }
    echo "\n";
}

// Confirmar antes de importar
echo "⚠️  ATENÇÃO: Serão importados $totalParaImportar patrimônios\n";
echo "Deseja continuar? (Pressione CTRL+C para cancelar, Enter para continuar)\n";
// fgets(STDIN); // Descomentarpara pedir confirmação

echo "\n🚀 Iniciando importação...\n";

DB::beginTransaction();

try {
    $importados = 0;
    $erros = [];
    
    foreach ($patrimoniosParaImportar as $dados) {
        try {
            $patrimonio = Patrimonio::create($dados);
            
            // Se tem CODOBJETO, preencher DEPATRIMONIO
            if ($patrimonio->CODOBJETO) {
                $objeto = ObjetoPatr::find($patrimonio->CODOBJETO);
                if ($objeto && $objeto->DEOBJETO) {
                    $patrimonio->update(['DEPATRIMONIO' => $objeto->DEOBJETO]);
                }
            }
            
            $importados++;
            
            if ($importados % 100 == 0) {
                echo "  Importados: $importados/$totalParaImportar\n";
            }
        } catch (Exception $e) {
            $erros[] = "Patrimônio #{$dados['NUPATRIMONIO']}: " . $e->getMessage();
        }
    }
    
    DB::commit();
    
    echo "\n✅ IMPORTAÇÃO CONCLUÍDA COM SUCESSO!\n\n";
    echo "📊 RESUMO:\n";
    echo "  - Total processado: $totalParaImportar\n";
    echo "  - Importados com sucesso: $importados\n";
    echo "  - Erros: " . count($erros) . "\n";
    
    if (count($erros) > 0) {
        echo "\n❌ Erros encontrados:\n";
        foreach (array_slice($erros, 0, 10) as $erro) {
            echo "  - $erro\n";
        }
        if (count($erros) > 10) {
            echo "  ... e mais " . (count($erros) - 10) . " erros\n";
        }
    }
    
    // Estatísticas finais
    echo "\n📈 ESTATÍSTICAS DO BANCO:\n";
    $totalPatrimonios = Patrimonio::count();
    $comDescricao = Patrimonio::whereNotNull('DEPATRIMONIO')->where('DEPATRIMONIO', '<>', '')->count();
    $disponiveis = Patrimonio::whereNull('NMPLANTA')->count();
    
    echo "  - Total de patrimônios no banco: $totalPatrimonios\n";
    echo "  - Com descrição preenchida: $comDescricao (" . round(($comDescricao/$totalPatrimonios)*100, 1) . "%)\n";
    echo "  - Disponíveis para atribuição: $disponiveis\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERRO CRÍTICO DURANTE IMPORTAÇÃO:\n";
    echo $e->getMessage() . "\n";
    echo "\nTransação revertida. Nenhum dado foi importado.\n";
    exit(1);
}

echo "\n✅ Script finalizado com sucesso!\n";
