<?php
/**
 * VALIDAÇÃO PRÉ-IMPORTAÇÃO
 * 
 * Este script VALIDA (sem executar) se:
 * 1. Todos os arquivos existem
 * 2. Usuários mencionados nos arquivos existem no banco
 * 3. Projetos referenciados existem
 * 4. Funcionários existem
 * 5. Encoding dos arquivos está correto
 * 
 * Execute ANTES de rodar qualquer importação
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Funcionario;
use App\Models\Tabfant;
use App\Models\LocalProjeto;
use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║            VALIDAÇÃO PRÉ-IMPORTAÇÃO                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "Data: " . now()->format('d/m/Y H:i:s') . "\n\n";

$baseDir = __DIR__ . '/../storage/imports/Novo import';
$errosCriticos = [];
$avisos = [];
$ok = 0;

// ========================================================================
// 1. VERIFICAR ARQUIVOS
// ========================================================================
echo "📁 [1/6] Verificando arquivos...\n";

$arquivos = [
    'Patrimonio.txt' => 'Patrimônios',
    'LocalProjeto.TXT' => 'Locais de Projeto',
    'Projetos_tabfantasia.txt' => 'Projetos (Tabfant)',
    'Hist_movpatr.TXT' => 'Histórico de Movimentações'
];

foreach ($arquivos as $arquivo => $descricao) {
    $caminho = "$baseDir/$arquivo";
    if (file_exists($caminho)) {
        $linhas = count(file($caminho));
        echo "  ✓ $descricao: $linhas linhas\n";
        $ok++;
    } else {
        $errosCriticos[] = "Arquivo não encontrado: $arquivo";
        echo "  ✗ $descricao: NÃO ENCONTRADO\n";
    }
}
echo "\n";

if (count($errosCriticos) > 0) {
    echo "❌ ERROS CRÍTICOS encontrados. Corrija antes de importar:\n";
    foreach ($errosCriticos as $erro) {
        echo "  - $erro\n";
    }
    exit(1);
}

// ========================================================================
// 2. VERIFICAR CONEXÃO COM BANCO
// ========================================================================
echo "🔌 [2/6] Verificando conexão com banco de dados...\n";
try {
    DB::connection()->getPdo();
    $dbName = DB::connection()->getDatabaseName();
    echo "  ✓ Conectado ao banco: $dbName\n";
    $ok++;
} catch (Exception $e) {
    $errosCriticos[] = "Falha na conexão com banco: " . $e->getMessage();
    echo "  ✗ Falha na conexão\n";
}
echo "\n";

if (count($errosCriticos) > 0) {
    echo "❌ Não é possível continuar sem conexão com o banco.\n";
    exit(1);
}

// ========================================================================
// 3. VERIFICAR USUÁRIOS
// ========================================================================
echo "👤 [3/6] Verificando usuários no arquivo Patrimonio.txt...\n";

$arquivoPatrimonio = "$baseDir/Patrimonio.txt";
$conteudo = file_get_contents($arquivoPatrimonio);

// Detectar encoding
$encoding = mb_detect_encoding($conteudo, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
echo "  ℹ️  Encoding detectado: " . ($encoding ?: 'Desconhecido') . "\n";

if ($encoding && $encoding !== 'UTF-8') {
    $avisos[] = "Arquivo Patrimonio.txt não está em UTF-8 ($encoding) - será convertido durante importação";
}

// Extrair coluna USUARIO (exemplo simplificado - pega palavras entre posições conhecidas)
preg_match_all('/USUARIO\s+([A-Z0-9\.]+)/i', $conteudo, $matches);
$usuariosArquivo = array_unique($matches[1] ?? []);
$usuariosArquivo = array_filter($usuariosArquivo, fn($u) => $u !== '<null>' && $u !== 'USUARIO');

echo "  ✓ Usuários únicos encontrados no arquivo: " . count($usuariosArquivo) . "\n";

// Carregar usuários do banco
$usuariosBanco = User::pluck('NMLOGIN')->map(fn($u) => strtoupper(trim($u)))->toArray();
echo "  ✓ Usuários cadastrados no banco: " . count($usuariosBanco) . "\n";

// Verificar se todos existem
$usuariosFaltando = [];
foreach ($usuariosArquivo as $usuario) {
    $usuarioUpper = strtoupper(trim($usuario));
    if (!in_array($usuarioUpper, $usuariosBanco) && $usuarioUpper !== 'SISTEMA') {
        $usuariosFaltando[] = $usuario;
    }
}

if (count($usuariosFaltando) > 0) {
    $avisos[] = count($usuariosFaltando) . " usuário(s) no arquivo não encontrados no banco (serão convertidos para SISTEMA)";
    echo "  ⚠️  Usuários não encontrados: " . implode(', ', array_slice($usuariosFaltando, 0, 10));
    if (count($usuariosFaltando) > 10) {
        echo " ... e mais " . (count($usuariosFaltando) - 10);
    }
    echo "\n";
} else {
    echo "  ✓ Todos os usuários existem no banco\n";
    $ok++;
}
echo "\n";

// ========================================================================
// 4. VERIFICAR FUNCIONÁRIOS
// ========================================================================
echo "👨‍💼 [4/6] Verificando matrículas de funcionários...\n";

preg_match_all('/CDMATRFUNCIONARIO\s+(\d+)/i', $conteudo, $matchesFunc);
$matriculasArquivo = array_unique(array_filter($matchesFunc[1] ?? [], fn($m) => $m !== '0'));

echo "  ✓ Matrículas únicas no arquivo: " . count($matriculasArquivo) . "\n";

$matriculasBanco = Funcionario::pluck('CDMATRFUNCIONARIO')->toArray();
echo "  ✓ Funcionários cadastrados: " . count($matriculasBanco) . "\n";

$matriculasFaltando = [];
foreach ($matriculasArquivo as $matr) {
    if (!in_array((int)$matr, $matriculasBanco)) {
        $matriculasFaltando[] = $matr;
    }
}

if (count($matriculasFaltando) > 0) {
    $avisos[] = count($matriculasFaltando) . " matrícula(s) não encontradas (será usado padrão 133838)";
    echo "  ⚠️  Matrículas não encontradas (primeiras 10): " . implode(', ', array_slice($matriculasFaltando, 0, 10)) . "\n";
} else {
    echo "  ✓ Todas as matrículas existem\n";
    $ok++;
}
echo "\n";

// ========================================================================
// 5. VERIFICAR PROJETOS
// ========================================================================
echo "📂 [5/6] Verificando projetos...\n";

preg_match_all('/CDPROJETO\s+(\d+)/i', $conteudo, $matchesProj);
$projetosArquivo = array_unique(array_filter($matchesProj[1] ?? [], fn($p) => $p !== '0'));

echo "  ✓ Projetos únicos no arquivo: " . count($projetosArquivo) . "\n";

$projetosBanco = Tabfant::pluck('CDPROJETO')->toArray();
echo "  ✓ Projetos cadastrados: " . count($projetosBanco) . "\n";

$projetosFaltando = [];
foreach ($projetosArquivo as $proj) {
    if (!in_array((int)$proj, $projetosBanco)) {
        $projetosFaltando[] = $proj;
    }
}

if (count($projetosFaltando) > 0) {
    $avisos[] = count($projetosFaltando) . " projeto(s) não encontrado(s) (será usado padrão 8)";
    echo "  ⚠️  Projetos não encontrados: " . implode(', ', array_slice($projetosFaltando, 0, 10)) . "\n";
} else {
    echo "  ✓ Todos os projetos existem\n";
    $ok++;
}
echo "\n";

// ========================================================================
// 6. VERIFICAR ESPAÇO EM DISCO E PERMISSÕES
// ========================================================================
echo "💾 [6/6] Verificando ambiente...\n";

$storageDir = __DIR__ . '/../storage/logs';
if (is_writable($storageDir)) {
    echo "  ✓ Diretório storage/logs é gravável\n";
    $ok++;
} else {
    $errosCriticos[] = "Diretório storage/logs NÃO é gravável";
    echo "  ✗ storage/logs não é gravável\n";
}

$espacoLivre = disk_free_space(__DIR__ . '/..');
$espacoLivreMB = round($espacoLivre / 1024 / 1024);
echo "  ✓ Espaço livre em disco: {$espacoLivreMB}MB\n";

if ($espacoLivreMB < 100) {
    $avisos[] = "Espaço em disco baixo ({$espacoLivreMB}MB). Recomendado: >100MB";
}

echo "\n";

// ========================================================================
// RESUMO FINAL
// ========================================================================
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                   RESUMO DA VALIDAÇÃO                      ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
echo "║  Verificações OK:        " . str_pad($ok, 3) . "                           ║\n";
echo "║  Avisos:                 " . str_pad(count($avisos), 3) . "                           ║\n";
echo "║  Erros Críticos:         " . str_pad(count($errosCriticos), 3) . "                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if (count($errosCriticos) > 0) {
    echo "❌ ERROS CRÍTICOS - CORRIJA ANTES DE IMPORTAR:\n";
    foreach ($errosCriticos as $erro) {
        echo "  • $erro\n";
    }
    echo "\n";
    exit(1);
}

if (count($avisos) > 0) {
    echo "⚠️  AVISOS (não impedem importação):\n";
    foreach ($avisos as $aviso) {
        echo "  • $aviso\n";
    }
    echo "\n";
}

echo "✅ VALIDAÇÃO CONCLUÍDA COM SUCESSO!\n\n";
echo "📋 PRÓXIMOS PASSOS:\n";
echo "  1. Fazer backup do banco:\n";
echo "     php scripts/backup_database.php\n\n";
echo "  2. Executar importações na ordem:\n";
echo "     php scripts/import_localprojeto.php\n";
echo "     php scripts/import_patrimonio_completo_v2.php\n";
echo "     php scripts/import_historico_movimentacao.php\n\n";
echo "  3. Ou executar tudo de uma vez:\n";
echo "     php scripts/run_importacao_completa.php\n\n";

echo "✅ Sistema pronto para importação!\n";
