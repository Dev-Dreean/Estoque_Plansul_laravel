<?php
// scripts/simulate_controller_filter.php
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
    echo "Usage: php scripts/simulate_controller_filter.php <valor_do_dropdown (NMLOGIN ou CD)>\n";
    exit(1);
}

$valorFiltro = trim($arg);
if ($valorFiltro === 'SISTEMA') {
    $count = Patrimonio::whereNull('USUARIO')->count();
    echo "SISTEMA => patrimonios sem USUARIO: {$count}\n";
    exit;
}

$loginFiltro = null;
$cdFiltro = null;

if (is_numeric($valorFiltro)) {
    $cdFiltro = $valorFiltro;
    $usuarioFiltro = User::where('CDMATRFUNCIONARIO', $valorFiltro)->first();
    $loginFiltro = $usuarioFiltro->NMLOGIN ?? null;
} else {
    $loginFiltro = $valorFiltro;
    $usuarioFiltro = User::where('NMLOGIN', $valorFiltro)->first();
    $cdFiltro = $usuarioFiltro->CDMATRFUNCIONARIO ?? null;
}

$query = Patrimonio::query();
$query->where(function($q) use ($loginFiltro, $cdFiltro, $valorFiltro) {
    if ($loginFiltro) {
        $q->where('USUARIO', $loginFiltro);
    }
    if ($cdFiltro) {
        $q->orWhere('CDMATRFUNCIONARIO', $cdFiltro);
    }
    if (is_numeric($valorFiltro)) {
        $q->orWhere('CDMATRFUNCIONARIO', $valorFiltro);
    }
});

$sql = $query->toSql();
$bindings = $query->getBindings();
echo "SQL gerada: {$sql}\n";
echo "Bindings: " . json_encode($bindings) . "\n";
$count = $query->count();
$sample = $query->limit(20)->get(['NUSEQPATR','NUPATRIMONIO','USUARIO','CDMATRFUNCIONARIO']);

echo "Filtro recebido: {$valorFiltro}\n";
if ($usuarioFiltro) {
    echo "Usuario encontrado: NMLOGIN={$usuarioFiltro->NMLOGIN}, CDMATRFUNCIONARIO={$usuarioFiltro->CDMATRFUNCIONARIO}\n";
} else {
    echo "Usuario não encontrado pelo filtro (pode ser CD inválido).\n";
}

echo "Patrimônios encontrados: {$count} (mostrando até 20)\n";
foreach ($sample as $p) {
    echo " - NUPATRIMONIO={$p->NUPATRIMONIO}, NUSEQPATR={$p->NUSEQPATR}, USUARIO={$p->USUARIO}, CDMATRFUNCIONARIO={$p->CDMATRFUNCIONARIO}\n";
}
