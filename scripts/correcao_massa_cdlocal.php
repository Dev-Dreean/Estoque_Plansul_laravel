<?php
/**
 * CORREÇÃO EM MASSA - CDLOCAL baseado em CDPROJETO
 * 
 * Este script corrige TODOS os patrimônios que têm CDLOCAL incorreto
 * baseado no projeto (CDPROJETO) que eles têm.
 * 
 * LÓGICA:
 * Para cada patrimônio com CDPROJETO:
 * 1. Buscar um local que tenha tabfant_id correspondente ao projeto
 * 2. Se encontrar, atualizar o CDLOCAL
 * 3. Se não encontrar, manter como está ou criar local
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\LocalProjeto;
use App\Models\Tabfant;
use Illuminate\Support\Facades\DB;

echo "🔧 CORREÇÃO EM MASSA - CDLOCAL baseado em CDPROJETO\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Criar mapeamento: CDPROJETO => Local ID
echo "📊 Criando mapeamento PROJETO => LOCAL...\n";

$mapeamentoProjetos = [];
$projetos = Tabfant::whereNotNull('CDPROJETO')->get();

foreach ($projetos as $projeto) {
    // Buscar local que tem esse projeto
    $local = LocalProjeto::where('tabfant_id', $projeto->id)->first();
    
    if ($local) {
        $mapeamentoProjetos[$projeto->CDPROJETO] = $local->id;
    }
}

echo "✅ Mapeamento criado: " . count($mapeamentoProjetos) . " projetos mapeados\n\n";

// Mostrar primeiros 20 mapeamentos
echo "Amostra do mapeamento:\n";
echo str_pad("CDPROJETO", 15) . str_pad("Local ID", 12) . "Local Nome\n";
echo str_repeat("─", 80) . "\n";

$count = 0;
foreach (array_slice($mapeamentoProjetos, 0, 20, true) as $cdproj => $localId) {
    $local = LocalProjeto::find($localId);
    echo str_pad($cdproj, 15) . str_pad($localId, 12) . ($local ? $local->delocal : 'N/A') . "\n";
    $count++;
}
echo "\n";

echo str_repeat("═", 80) . "\n\n";

// Análise de quantos patrimônios serão afetados
echo "🔍 ANÁLISE DE IMPACTO:\n\n";

$patrimoniosAfetados = [];
$patrimonios = Patrimonio::whereNotNull('CDPROJETO')->get();

foreach ($patrimonios as $p) {
    if (!isset($mapeamentoProjetos[$p->CDPROJETO])) {
        continue; // Pular se não temos mapeamento
    }
    
    $localCorreto = $mapeamentoProjetos[$p->CDPROJETO];
    
    if ($p->CDLOCAL != $localCorreto) {
        if (!isset($patrimoniosAfetados[$p->CDPROJETO])) {
            $patrimoniosAfetados[$p->CDPROJETO] = [
                'count' => 0,
                'de' => $p->CDLOCAL,
                'para' => $localCorreto
            ];
        }
        $patrimoniosAfetados[$p->CDPROJETO]['count']++;
    }
}

echo "Total de patrimônios que serão corrigidos: " . array_sum(array_column($patrimoniosAfetados, 'count')) . "\n\n";

echo "Primeiros 15 projetos que serão corrigidos:\n";
echo str_pad("CDPROJ", 10) . str_pad("Qtd", 8) . str_pad("Projeto", 30) . "Ação\n";
echo str_repeat("─", 80) . "\n";

$topProjetos = array_slice($patrimoniosAfetados, 0, 15, true);
foreach ($topProjetos as $cdproj => $info) {
    $projeto = Tabfant::where('CDPROJETO', $cdproj)->first();
    $nome = $projeto ? substr($projeto->NOMEPROJETO, 0, 28) : 'N/A';
    
    $localDe = LocalProjeto::find($info['de']);
    $localPara = LocalProjeto::find($info['para']);
    
    echo str_pad($cdproj, 10) . 
         str_pad($info['count'], 8) . 
         str_pad($nome, 30) . 
         "De: " . ($localDe ? $localDe->delocal : 'N/A') . 
         " → Para: " . ($localPara ? $localPara->delocal : 'N/A') . "\n";
}

echo "\n" . str_repeat("═", 80) . "\n\n";

echo "⚠️  ATENÇÃO: Esta correção afetará " . array_sum(array_column($patrimoniosAfetados, 'count')) . " patrimônios!\n\n";
echo "Deseja continuar? (s/N): ";
$resposta = trim(fgets(STDIN));

if (strtolower($resposta) !== 's') {
    echo "\n❌ Operação cancelada\n";
    exit(0);
}

// Criar backup
echo "\n📦 Criando backup...\n";
$timestamp = date('Y_m_d_His');
try {
    DB::statement("CREATE TABLE patr_backup_massa_{$timestamp} LIKE patr");
    DB::statement("INSERT INTO patr_backup_massa_{$timestamp} SELECT * FROM patr");
    echo "✅ Backup: patr_backup_massa_{$timestamp}\n\n";
} catch (Exception $e) {
    echo "❌ Erro ao criar backup: " . $e->getMessage() . "\n";
    exit(1);
}

// Executar correções
echo "🔄 Executando correções...\n";
DB::beginTransaction();

try {
    $totalCorrigidos = 0;
    $erros = 0;
    
    foreach ($mapeamentoProjetos as $cdprojeto => $localCorreto) {
        $updated = Patrimonio::where('CDPROJETO', $cdprojeto)
            ->where('CDLOCAL', '!=', $localCorreto)
            ->update(['CDLOCAL' => $localCorreto]);
        
        if ($updated > 0) {
            $totalCorrigidos += $updated;
            
            if ($totalCorrigidos % 500 == 0) {
                echo "  Corrigidos: {$totalCorrigidos}...\n";
            }
        }
    }
    
    DB::commit();
    
    echo "\n✅ CORREÇÃO CONCLUÍDA!\n\n";
    echo "📊 ESTATÍSTICAS:\n";
    echo "Total corrigidos: {$totalCorrigidos}\n";
    echo "Erros: {$erros}\n\n";
    
    // Verificar patrimônio 17546
    $p17546 = Patrimonio::where('NUPATRIMONIO', 17546)->first();
    if ($p17546) {
        $local = LocalProjeto::find($p17546->CDLOCAL);
        echo "✅ Verificação - Patrimônio 17546:\n";
        echo "   CDLOCAL: {$p17546->CDLOCAL}\n";
        echo "   CDPROJETO: {$p17546->CDPROJETO}\n";
        if ($local) {
            echo "   Local: {$local->delocal}\n";
        }
    }
    
    // Verificar inconsistências restantes
    echo "\n🔍 Verificando inconsistências restantes...\n";
    $restantes = DB::table('patr as p')
        ->join('locais_projeto as lp', 'p.CDLOCAL', '=', 'lp.id')
        ->leftJoin('tabfant as t', 'lp.tabfant_id', '=', 't.id')
        ->whereNotNull('p.CDPROJETO')
        ->whereNotNull('lp.tabfant_id')
        ->whereRaw('t.CDPROJETO != p.CDPROJETO')
        ->count();
    
    echo "Inconsistências restantes: {$restantes}\n";
    
    echo "\n💾 Para reverter:\n";
    echo "   DROP TABLE patr;\n";
    echo "   RENAME TABLE patr_backup_massa_{$timestamp} TO patr;\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Rollback executado.\n";
    exit(1);
}
