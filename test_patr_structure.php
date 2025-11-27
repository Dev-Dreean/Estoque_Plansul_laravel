<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Teste: Ver alguns registros da tabela patr para entender a estrutura
echo "=== Verificando estrutura de patr ===\n";

$registros = DB::table('patr')
    ->select('NUPATRIMONIO', 'USUARIO', 'DTOPERACAO')
    ->limit(20)
    ->get();

echo "Total de registros selecionados: " . count($registros) . "\n\n";

foreach ($registros as $reg) {
    echo "NUPATRIMONIO: {$reg->NUPATRIMONIO}\n";
    echo "  USUARIO: " . ($reg->USUARIO ?? 'NULL') . "\n";
    echo "  DTOPERACAO: " . ($reg->DTOPERACAO ?? 'NULL') . "\n";
    echo "\n";
}

// Teste: Contar quantos registros têm USUARIO preenchido
echo "=== Contagem de registros com USUARIO preenchido ===\n";
$comUsuario = DB::table('patr')
    ->where('USUARIO', '!=', null)
    ->where('USUARIO', '!=', '')
    ->count();

echo "Registros com USUARIO preenchido: " . $comUsuario . "\n";

// Teste: Contar registros com USUARIO vazio ou null
$vazios = DB::table('patr')->whereNull('USUARIO')->count();
$strings_vazias = DB::table('patr')->where('USUARIO', '')->count();

echo "Registros com USUARIO = NULL: " . $vazios . "\n";
echo "Registros com USUARIO = '': " . $strings_vazias . "\n";

// Teste: Buscar logins que realmente existem
echo "\n=== Logins DISTINTOS que realmente existem em patr ===\n";
$logins = DB::table('patr')
    ->whereNotNull('USUARIO')
    ->where('USUARIO', '!=', '')
    ->distinct()
    ->select('USUARIO')
    ->get();

echo "Total de logins distintos: " . count($logins) . "\n";
foreach ($logins as $login) {
    echo "  - " . $login->USUARIO . "\n";
}
