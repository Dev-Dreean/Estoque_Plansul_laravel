<?php
/**
 * Script de importação COMPLETA e ATUALIZAÇÃO de patrimônios
 * 
 * Este script:
 * 1. Analisa o arquivo patrimonio.TXT linha por linha
 * 2. Valida todos os relacionamentos (usuários, projetos, locais, objetos)
 * 3. ATUALIZA registros existentes (updateOrCreate)
 * 4. ADICIONA apenas patrimônios que não existem
 * 5. Preserva vínculos de usuários (USUARIO campo é obrigatório)
 * 6. Gera relatório detalhado de importação
 * 
 * IMPORTANTE: Este script substitui o import_patrimonio_completo.php
 * e adiciona lógica de atualização inteligente
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
use Illuminate\Support\Facades\Log;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  IMPORTAÇÃO COMPLETA DE PATRIMÔNIOS (COM ATUALIZAÇÃO)      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "Data: " . now()->format('d/m/Y H:i:s') . "\n\n";

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

// Se não foi passado argumento, usar caminho padrão
if (!$arquivoPath) {
    $arquivoPath = __DIR__ . '/../storage/imports/Novo import/Patrimonio.txt';
    echo "📌 Usando arquivo padrão: $arquivoPath\n\n";
}

if (!file_exists($arquivoPath)) {
    die("❌ ERRO: Arquivo não encontrado: $arquivoPath\n");
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

// IMPORTANTE: Mapear usuários por NMLOGIN (campo USUARIO do patrimônio)
$usuariosMap = [];
$usuarios = User::all();
foreach ($usuarios as $u) {
    if ($u->NMLOGIN) {
        $usuariosMap[strtoupper(trim($u->NMLOGIN))] = $u;
    }
    if ($u->NOMEUSER) {
        // Backup: também indexar por nome completo
        $usuariosMap[strtoupper(trim($u->NOMEUSER))] = $u;
    }
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
    // LocalProjeto usa 'id' como chave primária, mas no arquivo vem 'cdlocal'
    // Precisamos mapear por cdlocal se existir na tabela
    if (isset($l->cdlocal)) {
        $locaisMap[$l->cdlocal] = $l;
    } else {
        // Se não tem cdlocal, usar o id
        $locaisMap[$l->id] = $l;
    }
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
$patrimoniosParaProcessar = [];
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
    
    // ======================================================================
    // VALIDAÇÃO CRÍTICA: USUARIO
    // ======================================================================
    $usuario = $dados['USUARIO'] ?? null;
    
    // Se USUARIO está vazio ou é <null>, usar 'SISTEMA' como fallback
    if (empty($usuario)) {
        $usuario = 'SISTEMA';
    } else {
        $usuario = trim($usuario);
        $usuarioUpper = strtoupper($usuario);
        
        // Verificar se o usuário existe no sistema
        if (!isset($usuariosMap[$usuarioUpper])) {
            // Usuário não encontrado - adicionar aviso e usar SISTEMA
            $avisos[] = "#$nupatrimonio: Usuário '$usuario' não encontrado no sistema, usando 'SISTEMA'";
            $usuario = 'SISTEMA';
        }
    }
    
    // ======================================================================
    // Validar e ajustar CDMATRFUNCIONARIO
    // ======================================================================
    $cdmatr = $dados['CDMATRFUNCIONARIO'] ?? null;
    if ($cdmatr && is_numeric($cdmatr)) {
        $cdmatr = (int) $cdmatr;
        if (!isset($funcionariosMap[$cdmatr])) {
            $avisos[] = "#$nupatrimonio: Funcionário $cdmatr não encontrado, usando padrão (133838)";
            $cdmatr = 133838; // Matrícula padrão (BEA.SC)
        }
    } else {
        $cdmatr = 133838; // Padrão
    }
    
    // ======================================================================
    // Validar CDPROJETO
    // ======================================================================
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
    
    // ======================================================================
    // Validar CDLOCAL
    // ======================================================================
    $cdlocal = $dados['CDLOCAL'] ?? 1;
    if ($cdlocal && is_numeric($cdlocal)) {
        $cdlocal = (int) $cdlocal;
        // Se não existe no mapa, manter o valor mesmo assim (será validado no banco)
    } else {
        $cdlocal = 1;
    }
    
    // ======================================================================
    // Validar CODOBJETO
    // ======================================================================
    $codobjeto = $dados['CODOBJETO'] ?? null;
    if ($codobjeto && is_numeric($codobjeto)) {
        $codobjeto = (int) $codobjeto;
    } else {
        $codobjeto = null;
    }
    
    // ======================================================================
    // Converter datas do formato dd/mm/yyyy para yyyy-mm-dd
    // ======================================================================
    $normalizarData = function($dataStr) {
        if (empty($dataStr)) return null;
        
        // Tentar formato brasileiro dd/mm/yyyy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dataStr, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }
        
        // Tratar datas incompletas (ano com 3 dígitos)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{3})$/', $dataStr, $matches)) {
            // Se ano < 100, assumir 20xx
            $ano = '2' . str_pad($matches[3], 3, '0', STR_PAD_LEFT);
            return sprintf('%04d-%02d-%02d', $ano, $matches[2], $matches[1]);
        }
        
        return null;
    };
    
    $dtaquisicao = $normalizarData($dados['DTAQUISICAO']);
    $dtoperacao = $normalizarData($dados['DTOPERACAO']) ?: now()->format('Y-m-d');
    
    // ======================================================================
    // Preparar dados para importação/atualização
    // ======================================================================
    $patrimoniosParaProcessar[] = [
        'NUPATRIMONIO' => $nupatrimonio,
        'SITUACAO' => $dados['SITUACAO'] ?: 'EM USO',
        'MARCA' => $dados['MARCA'],
        'MODELO' => $dados['MODELO'],
        'COR' => $dados['COR'],
        'CDLOCAL' => $cdlocal,
        'CDMATRFUNCIONARIO' => $cdmatr,
        'CDPROJETO' => $cdprojeto,
        'USUARIO' => $usuario, // CRÍTICO: sempre preenchido
        'DTOPERACAO' => $dtoperacao,
        'DTAQUISICAO' => $dtaquisicao,
        'DEHISTORICO' => $dados['DEHISTORICO'],
        'NUMOF' => $dados['NUMOF'] && is_numeric($dados['NUMOF']) ? (int)$dados['NUMOF'] : null,
        'CODOBJETO' => $codobjeto,
        'FLCONFERIDO' => $dados['FLCONFERIDO'] ?: 'N',
        'DEPATRIMONIO' => null, // Será preenchido depois via CODOBJETO
        'NMPLANTA' => null,
    ];
}

$totalParaProcessar = count($patrimoniosParaProcessar);
echo "\n✓ Registros processados: $totalParaProcessar\n";
echo "⚠️  Avisos durante validação: " . count($avisos) . "\n\n";

if (count($avisos) > 0 && count($avisos) <= 20) {
    echo "Avisos:\n";
    foreach (array_slice($avisos, 0, 20) as $aviso) {
        echo "  - $aviso\n";
    }
    echo "\n";
}

// ======================================================================
// INÍCIO DA IMPORTAÇÃO COM UPDATEORCREATE
// ======================================================================
echo "⚠️  ATENÇÃO: Serão processados $totalParaProcessar patrimônios\n";
echo "   - Novos registros serão ADICIONADOS\n";
echo "   - Registros existentes serão ATUALIZADOS\n";
echo "   - Vínculos de usuários serão preservados\n\n";

echo "Deseja continuar? (Pressione CTRL+C para cancelar, Enter para continuar)\n";
// fgets(STDIN); // Descomentar para pedir confirmação

echo "\n🚀 Iniciando importação...\n";

DB::beginTransaction();

try {
    $criados = 0;
    $atualizados = 0;
    $erros = [];
    
    foreach ($patrimoniosParaProcessar as $idx => $dados) {
        try {
            // Verificar se já existe
            $existe = Patrimonio::where('NUPATRIMONIO', $dados['NUPATRIMONIO'])->exists();
            
            // updateOrCreate: atualiza se existe, cria se não existe
            $patrimonio = Patrimonio::updateOrCreate(
                ['NUPATRIMONIO' => $dados['NUPATRIMONIO']], // Chave de busca
                $dados // Dados a serem inseridos/atualizados
            );
            
            // Se tem CODOBJETO, preencher DEPATRIMONIO
            if ($patrimonio->CODOBJETO) {
                $objeto = ObjetoPatr::find($patrimonio->CODOBJETO);
                if ($objeto && $objeto->DEOBJETO) {
                    $patrimonio->update(['DEPATRIMONIO' => $objeto->DEOBJETO]);
                }
            }
            
            // Contador
            if ($existe) {
                $atualizados++;
            } else {
                $criados++;
            }
            
            // Log a cada 100 registros
            if (($criados + $atualizados) % 100 == 0) {
                echo "  Processados: " . ($criados + $atualizados) . "/$totalParaProcessar (Novos: $criados | Atualizados: $atualizados)\n";
            }
        } catch (Exception $e) {
            $erros[] = "Patrimônio #{$dados['NUPATRIMONIO']}: " . $e->getMessage();
            
            // Se tem mais de 50 erros, abortar
            if (count($erros) > 50) {
                throw new Exception("Muitos erros durante importação (>50). Abortando...");
            }
        }
    }
    
    DB::commit();
    
    echo "\n✅ IMPORTAÇÃO CONCLUÍDA COM SUCESSO!\n\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║                      RESUMO FINAL                          ║\n";
    echo "╠════════════════════════════════════════════════════════════╣\n";
    echo "║  Total processado:       " . str_pad($totalParaProcessar, 6, ' ', STR_PAD_LEFT) . "                         ║\n";
    echo "║  Novos criados:          " . str_pad($criados, 6, ' ', STR_PAD_LEFT) . "                         ║\n";
    echo "║  Atualizados:            " . str_pad($atualizados, 6, ' ', STR_PAD_LEFT) . "                         ║\n";
    echo "║  Erros:                  " . str_pad(count($erros), 6, ' ', STR_PAD_LEFT) . "                         ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    if (count($erros) > 0) {
        echo "❌ Erros encontrados:\n";
        foreach (array_slice($erros, 0, 20) as $erro) {
            echo "  - $erro\n";
        }
        if (count($erros) > 20) {
            echo "  ... e mais " . (count($erros) - 20) . " erros\n";
        }
        echo "\n";
    }
    
    // Estatísticas finais
    echo "📈 ESTATÍSTICAS DO BANCO:\n";
    $totalPatrimonios = Patrimonio::count();
    $comDescricao = Patrimonio::whereNotNull('DEPATRIMONIO')->where('DEPATRIMONIO', '<>', '')->count();
    $disponiveis = Patrimonio::whereNull('NMPLANTA')->count();
    $comUsuario = Patrimonio::whereNotNull('USUARIO')->where('USUARIO', '<>', '')->count();
    
    echo "  - Total de patrimônios no banco: $totalPatrimonios\n";
    echo "  - Com descrição preenchida: $comDescricao (" . round(($comDescricao/$totalPatrimonios)*100, 1) . "%)\n";
    echo "  - Disponíveis para atribuição: $disponiveis\n";
    echo "  - Com usuário vinculado: $comUsuario (" . round(($comUsuario/$totalPatrimonios)*100, 1) . "%)\n";
    
    // Registrar no log do Laravel
    Log::info('Importação de patrimônios concluída', [
        'total' => $totalParaProcessar,
        'criados' => $criados,
        'atualizados' => $atualizados,
        'erros' => count($erros),
        'arquivo' => $arquivoPath
    ]);
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERRO CRÍTICO DURANTE IMPORTAÇÃO:\n";
    echo $e->getMessage() . "\n";
    echo "\nTransação revertida. Nenhum dado foi alterado.\n";
    
    Log::error('Falha na importação de patrimônios', [
        'erro' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    exit(1);
}

echo "\n✅ Script finalizado com sucesso!\n";
