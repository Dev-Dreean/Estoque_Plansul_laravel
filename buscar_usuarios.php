<?php
// one-off: Buscar usuários Beatriz, Tiago, Theo
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "\n=== THEO ===\n";
$theo = User::where('NMLOGIN', 'THEO')->first();
if ($theo) echo $theo->NMLOGIN . " | " . $theo->NOMEUSER . "\n";

echo "\n=== TIAGO ===\n";
$tiago = User::where('NMLOGIN', 'TIAGOP')->first();
if ($tiago) echo $tiago->NMLOGIN . " | " . $tiago->NOMEUSER . "\n";

echo "\n=== BEATRIZ ===\n";
$bea = User::where('NMLOGIN', 'BEATRIZ.SC')->orWhere('NMLOGIN', 'BEATRIZ')->first();
if ($bea) {
    echo $bea->NMLOGIN . " | " . $bea->NOMEUSER . "\n";
} else {
    echo "Não encontrada como BEATRIZ.SC, procurando por 'BEA'...\n";
    $bea = User::where('NMLOGIN', 'BEA.SC')->orWhere('NMLOGIN', 'BEA')->first();
    if ($bea) echo $bea->NMLOGIN . " | " . $bea->NOMEUSER . "\n";
}

echo "\n=== TODOS COM ACESSO À CONTROLORIA ===\n";
$all = User::orderBy('NMLOGIN')->get(['NMLOGIN', 'NOMEUSER'])->take(20);
foreach ($all as $u) {
    echo $u->NMLOGIN . " | " . $u->NOMEUSER . "\n";
}
