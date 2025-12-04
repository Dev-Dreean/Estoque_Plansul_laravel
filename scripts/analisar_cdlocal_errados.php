<?php
/**
 * Script para analisar patrimônios com CDLOCAL incorreto
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\LocalProjeto;
use Illuminate\Support\Facades\DB;

echo "🔍 ANALISANDO PATRIMÔNIOS COM CDLOCAL INCORRETO\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Verificar estrutura da tabela locais_projeto
echo "📊 Verificando estrutura da tabela locais_projeto:\n";
$locais = LocalProjeto::select('id', 'cdlocal', 'delocal', 'tabfant_id')
    ->orderBy('id')
    ->get();

echo "Total de locais cadastrados: " . $locais->count() . "\n\n";
echo "Primeiros 20 locais:\n";
echo str_pad("ID", 6) . str_pad("cdlocal", 10) . str_pad("delocal", 40) . "tabfant_id\n";
echo str_repeat("-", 80) . "\n";
foreach ($locais->take(20) as $local) {
    echo str_pad($local->id, 6) . 
         str_pad($local->cdlocal, 10) . 
         str_pad(substr($local->delocal, 0, 38), 40) . 
         $local->tabfant_id . "\n";
}

echo "\n" . str_repeat("═", 80) . "\n\n";

// Análise dos patrimônios
echo "📦 Analisando patrimônios:\n";
$totalPatrimonios = Patrimonio::count();
echo "Total de patrimônios: {$totalPatrimonios}\n\n";

// Patrimônios onde CDLOCAL aponta para ID (padrão incorreto da importação)
$patrimoniosComCDLOCAL1 = Patrimonio::where('CDLOCAL', 1)->count();
echo "Patrimônios com CDLOCAL = 1: {$patrimoniosComCDLOCAL1}\n";

// Verificar se CDLOCAL está armazenando o ID da tabela ou o campo cdlocal
echo "\nAmostra de 10 patrimônios aleatórios:\n";
echo str_pad("NUPATR", 10) . str_pad("CDLOCAL", 12) . "Local (delocal)\n";
echo str_repeat("-", 60) . "\n";

$amostra = Patrimonio::inRandomOrder()->take(10)->get();
foreach ($amostra as $p) {
    $local = LocalProjeto::find($p->CDLOCAL);
    $localNome = $local ? $local->delocal : 'NÃO ENCONTRADO';
    echo str_pad($p->NUPATRIMONIO, 10) . 
         str_pad($p->CDLOCAL, 12) . 
         $localNome . "\n";
}

echo "\n" . str_repeat("═", 80) . "\n\n";

// Verificar patrimônios que estão usando ID quando deveriam usar cdlocal
echo "🔍 PROBLEMA IDENTIFICADO:\n";
echo "A coluna CDLOCAL na tabela 'patr' está armazenando o ID da tabela locais_projeto\n";
echo "Quando deveria armazenar o campo 'cdlocal' da tabela locais_projeto\n\n";

echo "Exemplo:\n";
echo "• Patrimônio 17546 tem CDLOCAL=1 (que é o ID da tabela locais_projeto)\n";
echo "• ID 1 aponta para o local: 'SEDE CIDASC' (cdlocal=1)\n";
echo "• Mas deveria apontar para ID 8: 'ARARANGUA' (cdlocal=8)\n\n";

echo "💡 SOLUÇÃO:\n";
echo "Precisamos de um mapeamento correto entre:\n";
echo "• O que está no banco (CDLOCAL como ID)\n";
echo "• O que deveria ser (CDLOCAL como código do local)\n\n";

// Verificar se há padrão de erro
echo "📋 Verificando padrões de mapeamento:\n";
$mapeamentos = DB::table('patr')
    ->select('CDLOCAL', DB::raw('COUNT(*) as total'))
    ->whereNotNull('CDLOCAL')
    ->groupBy('CDLOCAL')
    ->orderBy('CDLOCAL')
    ->limit(30)
    ->get();

echo "\nDistribuição de CDLOCAL nos patrimônios:\n";
echo str_pad("CDLOCAL", 12) . str_pad("Total Patr.", 15) . "Local (delocal)\n";
echo str_repeat("-", 70) . "\n";

foreach ($mapeamentos as $map) {
    $local = LocalProjeto::find($map->CDLOCAL);
    $info = $local ? "{$local->delocal} (cdlocal={$local->cdlocal})" : "NÃO ENCONTRADO";
    echo str_pad($map->CDLOCAL, 12) . str_pad($map->total, 15) . $info . "\n";
}
