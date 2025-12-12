<?php
// one-off: Verificar dados de UF e vincular patrimonios

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "🔍 ANÁLISE: Vinculação de Patrimonios com UF por Projeto/Local\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

// 1. Verificar se patr tem UF
$hasUFInPatr = DB::getSchemaBuilder()->hasColumn('patr', 'UF');
echo "1️⃣  Tabela 'patr' tem coluna UF: " . ($hasUFInPatr ? "✅ SIM" : "❌ NÃO") . "\n";

// 2. Verificar se locais_projeto tem UF
$hasUFInLocais = DB::getSchemaBuilder()->hasColumn('locais_projeto', 'UF');
echo "2️⃣  Tabela 'locais_projeto' tem coluna UF: " . ($hasUFInLocais ? "✅ SIM" : "❌ NÃO") . "\n\n";

// 3. Verificar dados de UF em tabfant
echo "3️⃣  UF em TABFANT (Projetos):\n";
$projetos = DB::table('tabfant')
    ->select('id', 'CDPROJETO', 'NOMEPROJETO', 'UF', 'LOCAL')
    ->limit(10)
    ->get();

foreach ($projetos as $p) {
    echo sprintf("   • CDPROJETO=%s | %s | UF=%s | LOCAL=%s\n", 
        $p->CDPROJETO, 
        substr($p->NOMEPROJETO, 0, 30), 
        $p->UF ?? 'NULL',
        $p->LOCAL ?? 'NULL'
    );
}

echo "\n4️⃣  Contagem de Projetos:\n";
$total = DB::table('tabfant')->count();
$comUF = DB::table('tabfant')->whereNotNull('UF')->count();
$semUF = DB::table('tabfant')->whereNull('UF')->count();
echo "   • Total: $total\n";
echo "   • Com UF: $comUF\n";
echo "   • Sem UF: $semUF\n";

echo "\n5️⃣  Amostra de LOCAIS_PROJETO:\n";
$locais = DB::table('locais_projeto')
    ->select('id', 'cdlocal', 'delocal', 'tabfant_id')
    ->limit(5)
    ->get();

foreach ($locais as $l) {
    $proj = DB::table('tabfant')->find($l->tabfant_id);
    echo sprintf("   • CDLOCAL=%s | %s | Projeto=%s\n", 
        $l->cdlocal,
        substr($l->delocal, 0, 25),
        $proj ? $proj->NOMEPROJETO : 'N/A'
    );
}

echo "\n6️⃣  Amostra de PATRIMÔNIOS:\n";
$patrimonios = DB::table('patr')
    ->select('NUSEQPATR', 'DEPATRIMONIO', 'CDPROJETO', 'CDLOCAL')
    ->limit(5)
    ->get();

foreach ($patrimonios as $p) {
    $proj = DB::table('tabfant')->where('CDPROJETO', $p->CDPROJETO)->first();
    $local = DB::table('locais_projeto')->where('cdlocal', $p->CDLOCAL)->first();
    echo sprintf("   • NUSEQ=%s | %s | Proj=%s | Local=%s\n",
        $p->NUSEQPATR,
        substr($p->DEPATRIMONIO, 0, 20),
        $proj ? ($proj->UF ?? 'NULL') : 'PROJ_NOT_FOUND',
        $local ? ($local->delocal ?? 'N/A') : 'LOCAL_NOT_FOUND'
    );
}

echo "\n═══════════════════════════════════════════════════════════════════════\n";
echo "📌 PRÓXIMOS PASSOS:\n";
echo "───────────────────────────────────────────────────────────────────────\n";
echo "1. Criar migration para adicionar UF em 'patr'\n";
echo "2. Criar migration para adicionar UF em 'locais_projeto'\n";
echo "3. Criar script para popular UF baseado em tabfant → patr\n";
echo "4. Atualizar modelos com relacionamentos UF\n";
echo "5. Criar filtros de UF na tela de patrimônios\n";
echo "═══════════════════════════════════════════════════════════════════════\n";
?>
