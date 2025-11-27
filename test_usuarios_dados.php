<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Buscar últimos registros para ver se há algum com USUARIO preenchido
echo "=== Últimos 50 registros e seus USUARIO ===\n";
$registros = DB::table('patr')
    ->select('NUPATRIMONIO', 'USUARIO', 'DEHISTORICO')
    ->orderByDesc('NUSEQPATR')
    ->limit(50)
    ->get();

echo "Total: " . count($registros) . "\n\n";

$comUsuario = 0;
foreach ($registros as $reg) {
    if ($reg->USUARIO) {
        $comUsuario++;
        echo "NUPATRIMONIO: {$reg->NUPATRIMONIO}, USUARIO: {$reg->USUARIO}, HISTORICO: " . substr($reg->DEHISTORICO ?? '', 0, 50) . "\n";
    }
}

echo "\nTotal com USUARIO preenchido nestes 50: " . $comUsuario . "\n";

// Tentar buscar em TODA a tabela
echo "\n=== Verificação geral ===\n";
$countTotal = DB::table('patr')->count();
$countComUsuario = DB::table('patr')->whereNotNull('USUARIO')->where('USUARIO', '!=', '')->count();

echo "Total de registros em patr: " . $countTotal . "\n";
echo "Total com USUARIO preenchido: " . $countComUsuario . "\n";

if ($countComUsuario > 0) {
    echo "\n=== Exemplos com USUARIO ===\n";
    $exemplos = DB::table('patr')
        ->whereNotNull('USUARIO')
        ->where('USUARIO', '!=', '')
        ->select('NUPATRIMONIO', 'USUARIO', 'DEHISTORICO')
        ->limit(10)
        ->get();
    
    foreach ($exemplos as $ex) {
        echo "  - NUPATRIMONIO: {$ex->NUPATRIMONIO}, USUARIO: {$ex->USUARIO}\n";
    }
}
