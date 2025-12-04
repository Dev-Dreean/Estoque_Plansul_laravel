<?php
/**
 * Script para verificar TODOS os patrimônios e corrigir inconsistências
 * entre CDLOCAL e CDPROJETO
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\LocalProjeto;
use App\Models\Tabfant;
use Illuminate\Support\Facades\DB;

echo "🔍 VERIFICAÇÃO COMPLETA - CDLOCAL vs CDPROJETO\n";
echo "════════════════════════════════════════════════════════════\n\n";

echo "Analisando todos os patrimônios...\n";

// Buscar patrimônios onde o local não corresponde ao projeto
$inconsistencias = DB::table('patr as p')
    ->join('locais_projeto as lp', 'p.CDLOCAL', '=', 'lp.id')
    ->leftJoin('tabfant as t', 'lp.tabfant_id', '=', 't.id')
    ->whereNotNull('p.CDPROJETO')
    ->whereNotNull('lp.tabfant_id')
    ->whereRaw('t.CDPROJETO != p.CDPROJETO')
    ->select(
        'p.NUPATRIMONIO',
        'p.CDLOCAL',
        'p.CDPROJETO as patrimonio_cdprojeto',
        'lp.delocal',
        'lp.cdlocal',
        't.CDPROJETO as local_cdprojeto',
        't.NOMEPROJETO'
    )
    ->get();

echo "Total de inconsistências encontradas: " . $inconsistencias->count() . "\n\n";

if ($inconsistencias->count() > 0) {
    echo "Primeiras 50 inconsistências:\n";
    echo str_repeat("─", 120) . "\n";
    echo str_pad("NUPATR", 10) . 
         str_pad("CDLOCAL", 10) . 
         str_pad("Proj.Patr", 12) . 
         str_pad("Proj.Local", 12) . 
         "Local\n";
    echo str_repeat("─", 120) . "\n";
    
    foreach ($inconsistencias->take(50) as $inc) {
        echo str_pad($inc->NUPATRIMONIO, 10) .
             str_pad($inc->CDLOCAL, 10) .
             str_pad($inc->patrimonio_cdprojeto, 12) .
             str_pad($inc->local_cdprojeto, 12) .
             substr($inc->delocal, 0, 40) . "\n";
    }
    
    if ($inconsistencias->count() > 50) {
        echo "\n... e mais " . ($inconsistencias->count() - 50) . " casos\n";
    }
}

echo "\n" . str_repeat("═", 120) . "\n\n";

// Agrupar por projeto
echo "📊 INCONSISTÊNCIAS AGRUPADAS POR PROJETO:\n\n";

$porProjeto = [];
foreach ($inconsistencias as $inc) {
    $key = "{$inc->patrimonio_cdprojeto}";
    if (!isset($porProjeto[$key])) {
        $porProjeto[$key] = [
            'projeto' => $inc->patrimonio_cdprojeto,
            'count' => 0,
            'locais' => []
        ];
    }
    $porProjeto[$key]['count']++;
    
    $localKey = $inc->CDLOCAL;
    if (!isset($porProjeto[$key]['locais'][$localKey])) {
        $porProjeto[$key]['locais'][$localKey] = [
            'nome' => $inc->delocal,
            'count' => 0
        ];
    }
    $porProjeto[$key]['locais'][$localKey]['count']++;
}

// Ordenar por quantidade
uasort($porProjeto, function($a, $b) {
    return $b['count'] - $a['count'];
});

foreach (array_slice($porProjeto, 0, 10) as $proj) {
    $nomeProjeto = Tabfant::where('CDPROJETO', $proj['projeto'])->first();
    $nome = $nomeProjeto ? $nomeProjeto->NOMEPROJETO : 'Não encontrado';
    
    echo "Projeto {$proj['projeto']} ({$nome}): {$proj['count']} patrimônios\n";
    
    foreach ($proj['locais'] as $local) {
        echo "   • {$local['nome']}: {$local['count']} patrimônios\n";
    }
    echo "\n";
}

echo str_repeat("═", 120) . "\n\n";
echo "💡 RESUMO:\n";
echo "• Total de patrimônios verificados: " . Patrimonio::whereNotNull('CDPROJETO')->count() . "\n";
echo "• Patrimônios com inconsistência: " . $inconsistencias->count() . "\n";
echo "• Patrimônios corretos: " . (Patrimonio::whereNotNull('CDPROJETO')->count() - $inconsistencias->count()) . "\n";
