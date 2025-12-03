<?php
// scripts/test_cadastrador_filter.php
// Uso: php test_cadastrador_filter.php <matricula|login|nome>

use Illuminate\Contracts\Console\Kernel;
use App\Models\User;
use App\Models\Patrimonio;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$arg = $argv[1] ?? null;
if (!$arg) {
    echo "Usage: php scripts/test_cadastrador_filter.php <matricula|login|parte-do-nome>\n";
    exit(1);
}

$term = trim($arg);
$isNumeric = is_numeric($term);

echo "Procurando usuário para termo: '{$term}'\n";

if ($isNumeric) {
    // procurar por CDMATRFUNCIONARIO
    $user = User::where('CDMATRFUNCIONARIO', $term)->first();
    if (!$user) {
        echo "Usuário com CDMATRFUNCIONARIO={$term} não encontrado. Tentando como NUSEQUSUARIO...\n";
        $user = User::find((int) $term);
    }
} else {
    // tentar pelo NMLOGIN exato
    $user = User::where('NMLOGIN', $term)->first();
    if (!$user) {
        // tentar por NOMEUSER contendo
        $user = User::where('NOMEUSER', 'like', "%{$term}%")->first();
    }
}

if (!$user) {
    echo "Usuário não encontrado para termo '{$term}'. Saindo.\n";
    exit(1);
}

echo "Encontrado: NUSEQUSUARIO={$user->NUSEQUSUARIO}, NMLOGIN={$user->NMLOGIN}, NOMEUSER={$user->NOMEUSER}, CDMATRFUNCIONARIO={$user->CDMATRFUNCIONARIO}\n\n";

$login = $user->NMLOGIN;
$cd = $user->CDMATRFUNCIONARIO;

// Buscar patrimonios por USUARIO (login)
$byLogin = Patrimonio::where('USUARIO', $login)->limit(50)->get();
$byMatr = collect();
if ($cd) {
    $byMatr = Patrimonio::where('CDMATRFUNCIONARIO', $cd)->limit(50)->get();
}

echo "Patrimônios com USUARIO = {$login}: " . $byLogin->count() . " (mostrando até 50)\n";
foreach ($byLogin as $p) {
    echo " - NUPATRIMONIO={$p->NUPATRIMONIO}, NUSEQPATR={$p->NUSEQPATR}, USUARIO={$p->USUARIO}, CDMATRFUNCIONARIO={$p->CDMATRFUNCIONARIO}\n";
}

if ($cd) {
    echo "\nPatrimônios com CDMATRFUNCIONARIO = {$cd}: " . $byMatr->count() . " (mostrando até 50)\n";
    foreach ($byMatr as $p) {
        echo " - NUPATRIMONIO={$p->NUPATRIMONIO}, NUSEQPATR={$p->NUSEQPATR}, USUARIO={$p->USUARIO}, CDMATRFUNCIONARIO={$p->CDMATRFUNCIONARIO}\n";
    }
}

// Diagnóstico adicional: quantos patrimônios apontam para um usuário específico (BEATRIZ)
$beatrizLike = Patrimonio::where('USUARIO', 'like', '%BEATRIZ%')->count();
if ($beatrizLike > 0) {
    echo "\nAVISO: existem {$beatrizLike} patrimônios com USUARIO contendo 'BEATRIZ' (isso pode explicar porque aparece BEATRIZ quando filtra outro usuário).\n";
}

echo "\nTeste concluído.\n";
