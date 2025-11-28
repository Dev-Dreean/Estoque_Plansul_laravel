<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Bootstrap a kernel to access facades
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$telaOrder = ['1000','1008','1009','1002','1006','1003','1004','1005','1007'];
$telaRotas = [
    '1000' => route('patrimonios.index'),
    '1008' => route('dashboard'),
    '1009' => route('projetos.index'),
    '1002' => route('usuarios.index'),
    '1006' => route('cadastro-tela.index'),
    '1004' => route('cadastro-tela.index'),
    // '1003' => route('usuarios.index'), // mapped to 1002 already
    // 1005 and 1007 don't have dedicated route names, skip mapping if not found
];

$todas = DB::table('acessotela')->where('FLACESSO','S')->get()->sortBy(function($t) use($telaOrder){
    $idx = array_search((string)$t->NUSEQTELA, $telaOrder);
    return $idx === false ? 10000 + (int)$t->NUSEQTELA : $idx;
});

foreach ($todas as $t) {
    $hasRoute = isset($telaRotas[$t->NUSEQTELA]);
    echo $t->NUSEQTELA . " - " . $t->DETELA . " - " . ($hasRoute ? 'ROTA_MAPPING' : 'NROTA') . PHP_EOL;
}

?>