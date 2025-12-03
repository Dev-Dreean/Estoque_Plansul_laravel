<?php
// scripts/check_sistema.php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Contagens na tabela patr (campo USUARIO):\n";
$countNull = DB::table('patr')->whereNull('USUARIO')->count();
$countEmpty = DB::table('patr')->where('USUARIO', '')->count();
$countSistema = DB::table('patr')->where('USUARIO', 'SISTEMA')->count();
$countSistemaLower = DB::table('patr')->whereRaw("LOWER(USUARIO) = ?", ['sistema'])->count();

echo " - NULL: {$countNull}\n";
echo " - empty string: {$countEmpty}\n";
echo " - 'SISTEMA' exact: {$countSistema}\n";
echo " - 'sistema' lower match: {$countSistemaLower}\n";

echo "\nAmostra (10) de registros com USUARIO IS NULL:\n";
$rows = DB::table('patr')->select('NUSEQPATR','NUPATRIMONIO','USUARIO','CDMATRFUNCIONARIO')->whereNull('USUARIO')->limit(10)->get();
foreach ($rows as $r) {
    echo " - NUPATRIMONIO={$r->NUPATRIMONIO}, NUSEQPATR={$r->NUSEQPATR}, USUARIO=" . var_export($r->USUARIO, true) . ", CDMATRFUNCIONARIO={$r->CDMATRFUNCIONARIO}\n";
}

echo "\nAmostra (10) de registros com USUARIO = '' (vazio):\n";
$rows = DB::table('patr')->select('NUSEQPATR','NUPATRIMONIO','USUARIO','CDMATRFUNCIONARIO')->where('USUARIO','')->limit(10)->get();
foreach ($rows as $r) {
    echo " - NUPATRIMONIO={$r->NUPATRIMONIO}, NUSEQPATR={$r->NUSEQPATR}, USUARIO=''. CDMATRFUNCIONARIO={$r->CDMATRFUNCIONARIO}\n";
}

echo "\nAmostra (10) de registros com USUARIO like '%SISTEMA%':\n";
$rows = DB::table('patr')->select('NUSEQPATR','NUPATRIMONIO','USUARIO','CDMATRFUNCIONARIO')->where('USUARIO','like','%SISTEMA%')->limit(10)->get();
foreach ($rows as $r) {
    echo " - NUPATRIMONIO={$r->NUPATRIMONIO}, NUSEQPATR={$r->NUSEQPATR}, USUARIO={$r->USUARIO}, CDMATRFUNCIONARIO={$r->CDMATRFUNCIONARIO}\n";
}

echo "\nConsulta rápida: quantos registros totais?\n";
$total = DB::table('patr')->count();
echo "Total: {$total}\n";

echo "\nScript concluído.\n";
