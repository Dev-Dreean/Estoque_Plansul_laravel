<?php
/**
 * Script para investigar o projeto 100001 e sua relação com locais
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\LocalProjeto;
use App\Models\Tabfant;
use Illuminate\Support\Facades\DB;

echo "🔍 INVESTIGANDO PROJETO 100001\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Buscar projeto 100001
echo "📋 Buscando projeto 100001 na tabela tabfant:\n";
$projeto = Tabfant::where('CDPROJETO', 100001)->first();

if ($projeto) {
    echo "✅ Projeto encontrado:\n";
    echo "   ID: {$projeto->id}\n";
    echo "   CDPROJETO: {$projeto->CDPROJETO}\n";
    echo "   NOMEPROJETO: {$projeto->NOMEPROJETO}\n";
    echo "   flativo: {$projeto->flativo}\n";
} else {
    echo "❌ Projeto 100001 NÃO encontrado na tabela tabfant!\n";
}

echo "\n" . str_repeat("─", 80) . "\n\n";

// Buscar locais que deveriam estar associados ao projeto 100001
echo "📍 Buscando locais associados ao projeto 100001:\n";
$locaisComProjeto = LocalProjeto::whereHas('projeto', function($q) {
    $q->where('CDPROJETO', 100001);
})->get();

if ($locaisComProjeto->count() > 0) {
    echo "Encontrados " . $locaisComProjeto->count() . " locais:\n";
    foreach ($locaisComProjeto as $local) {
        $proj = $local->projeto;
        echo "   • ID: {$local->id}, cdlocal: {$local->cdlocal}, delocal: {$local->delocal}\n";
        echo "     Projeto: {$proj->CDPROJETO} - {$proj->NOMEPROJETO}\n";
    }
} else {
    echo "⚠️  Nenhum local encontrado com projeto 100001\n";
}

echo "\n" . str_repeat("─", 80) . "\n\n";

// Buscar local CDLOCAL=1 (SEDE CIDASC)
echo "🏢 Verificando local CDLOCAL=1:\n";
$localSede = LocalProjeto::where('cdlocal', 1)->first();

if ($localSede) {
    echo "Local encontrado:\n";
    echo "   ID: {$localSede->id}\n";
    echo "   cdlocal: {$localSede->cdlocal}\n";
    echo "   delocal: {$localSede->delocal}\n";
    echo "   tabfant_id: {$localSede->tabfant_id}\n";
    
    if ($localSede->tabfant_id) {
        $projAssociado = Tabfant::find($localSede->tabfant_id);
        if ($projAssociado) {
            echo "   Projeto associado: {$projAssociado->CDPROJETO} - {$projAssociado->NOMEPROJETO}\n";
        }
    }
}

echo "\n" . str_repeat("─", 80) . "\n\n";

// Verificar patrimônio 17546
echo "📦 Verificando patrimônio 17546:\n";
$p = Patrimonio::where('NUPATRIMONIO', 17546)->first();

if ($p) {
    echo "Dados atuais no banco:\n";
    echo "   NUPATRIMONIO: {$p->NUPATRIMONIO}\n";
    echo "   CDLOCAL: {$p->CDLOCAL}\n";
    echo "   CDPROJETO: {$p->CDPROJETO}\n";
    echo "   SITUACAO: {$p->SITUACAO}\n";
    
    if ($p->CDLOCAL) {
        $local = LocalProjeto::find($p->CDLOCAL);
        if ($local) {
            echo "\n   Local atual (ID {$p->CDLOCAL}):\n";
            echo "   • delocal: {$local->delocal}\n";
            echo "   • cdlocal: {$local->cdlocal}\n";
        }
    }
}

echo "\n" . str_repeat("═", 80) . "\n\n";
echo "💡 ANÁLISE:\n\n";
echo "Segundo o relato:\n";
echo "• Projeto 100001 é uma EXTENSÃO DA SEDE (código expandido)\n";
echo "• CDLOCAL deveria ser relacionado ao código 8 quando o projeto é 100001\n";
echo "• No arquivo TXT: CDLOCAL=1, CDPROJETO=100001\n\n";

echo "Interpretação:\n";
echo "• CDLOCAL=1 no arquivo significa 'SEDE CIDASC'\n";
echo "• CDPROJETO=100001 é a extensão específica da sede\n";
echo "• A questão é: o local ID=1 (cdlocal=1) deveria ter tabfant_id apontando\n";
echo "  para o projeto 100001, OU deveria existir um local específico para o\n";
echo "  projeto 100001?\n\n";

// Buscar todos os patrimônios com projeto 100001
echo str_repeat("─", 80) . "\n\n";
echo "📊 Patrimônios com CDPROJETO=100001:\n";
$patrsComProjeto100001 = Patrimonio::where('CDPROJETO', 100001)->count();
echo "Total: {$patrsComProjeto100001}\n\n";

// Distribuição por CDLOCAL
echo "Distribuição por CDLOCAL:\n";
$distribuicao = DB::table('patr')
    ->select('CDLOCAL', DB::raw('COUNT(*) as total'))
    ->where('CDPROJETO', 100001)
    ->whereNotNull('CDLOCAL')
    ->groupBy('CDLOCAL')
    ->orderBy('total', 'desc')
    ->get();

foreach ($distribuicao as $dist) {
    $local = LocalProjeto::find($dist->CDLOCAL);
    $nomeLocal = $local ? $local->delocal : 'NÃO ENCONTRADO';
    echo "   CDLOCAL {$dist->CDLOCAL}: {$dist->total} patrimônios ({$nomeLocal})\n";
}

echo "\n" . str_repeat("═", 80) . "\n\n";
echo "🎯 PRÓXIMAS AÇÕES:\n";
echo "1. Verificar se existe um local específico para o projeto 100001\n";
echo "2. Se não existir, criar esse local\n";
echo "3. Ou ajustar o local CDLOCAL=1 para ter tabfant_id do projeto 100001\n";
echo "4. Atualizar todos os patrimônios que têm CDPROJETO=100001\n";
