<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "📋 Projetos disponíveis no banco:\n\n";

$projetos = DB::table('tabfant')
    ->orderBy('CDPROJETO')
    ->select('id', 'CDPROJETO', 'NOMEPROJETO')
    ->get();

foreach ($projetos as $p) {
    echo sprintf("ID: %-6s | Código: %-8s | Nome: %s\n", $p->id, $p->CDPROJETO, $p->NOMEPROJETO);
}

echo "\n✅ Total de projetos: " . count($projetos) . "\n";
