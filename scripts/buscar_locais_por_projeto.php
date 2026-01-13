<?php
// one-off: Buscar locais válidos do projeto SEDE
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 Locais do projeto SEDE (código 8):\n\n";

$locais = DB::table('locais_projeto')
    ->join('tabfant', 'locais_projeto.tabfant_id', '=', 'tabfant.id')
    ->where('tabfant.CDPROJETO', 8)
    ->select('locais_projeto.cdlocal', 'locais_projeto.delocal')
    ->limit(10)
    ->get();

foreach ($locais as $l) {
    echo "   • {$l->cdlocal} - {$l->delocal}\n";
}

echo "\n✅ Total de locais SEDE: " . count($locais) . "\n";
