<?php
// scripts/update_cadastrador_6817.php

use Illuminate\Contracts\Console\Kernel;
use App\Models\User;
use App\Models\Patrimonio;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Iniciando atualização do cadastrador do patrimônio 6817...\n";

$login = 'BEA.SC';
$numeroPatrimonio = 6817;

$user = User::where('NMLOGIN', $login)->first();
if (!$user) {
    echo "Usuário '{$login}' não encontrado. Abortando.\n";
    exit(1);
}

// Procurar pelo patrimônio pelo número (NUPATRIMONIO)
$patr = Patrimonio::where('NUPATRIMONIO', $numeroPatrimonio)->first();
if (!$patr) {
    // Tentar por NUSEQPATR caso o número informado seja a PK
    $patr = Patrimonio::where('NUSEQPATR', $numeroPatrimonio)->first();
}

if (!$patr) {
    echo "Patrimônio com número {$numeroPatrimonio} não encontrado. Verifique o número.\n";
    exit(1);
}

$oldUsuario = $patr->USUARIO ?? '(vazio)';
$oldMat = $patr->CDMATRFUNCIONARIO ?? '(vazio)';

// Atualizar campos
$patr->USUARIO = $user->NMLOGIN;
if (!empty($user->CDMATRFUNCIONARIO)) {
    $patr->CDMATRFUNCIONARIO = $user->CDMATRFUNCIONARIO;
}
$patr->DTOPERACAO = now();

try {
    $patr->save();
    echo "Atualização concluída:\n";
    echo " - Patrimônio: {$patr->NUPATRIMONIO} (NUSEQPATR: {$patr->NUSEQPATR})\n";
    echo " - USUARIO: '{$oldUsuario}' -> '{$patr->USUARIO}'\n";
    echo " - CDMATRFUNCIONARIO: '{$oldMat}' -> '{$patr->CDMATRFUNCIONARIO}'\n";
    exit(0);
} catch (\Throwable $e) {
    echo "Falha ao salvar patrimônio: " . $e->getMessage() . "\n";
    exit(1);
}
