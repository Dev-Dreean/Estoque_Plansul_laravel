<?php
// one-off: Script para corrigir patrimônios antigos - será deletado após execução

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use Illuminate\Support\Facades\Log;

echo "🔍 Analisando patrimônios antigos...\n";

// 1. Patrimônios com CODOBJETO=0
$patrimoniosZero = Patrimonio::where('CODOBJETO', 0)
    ->orWhereNull('CODOBJETO')
    ->limit(50)
    ->get();

echo "\n📋 CODOBJETO=0 ou NULL (primeiros 50):\n";
foreach ($patrimoniosZero as $p) {
    echo "  Patrimônio #{$p->NUPATRIMONIO}: DEPATRIMONIO='{$p->DEPATRIMONIO}', CODOBJETO={$p->CODOBJETO}\n";
}

// 2. Patrimônios com CDPROJETO=0
$patrimoniosProjetoZero = Patrimonio::where('CDPROJETO', 0)
    ->orWhereNull('CDPROJETO')
    ->limit(50)
    ->get();

echo "\n📋 CDPROJETO=0 ou NULL (primeiros 50):\n";
foreach ($patrimoniosProjetoZero as $p) {
    echo "  Patrimônio #{$p->NUPATRIMONIO}: CDPROJETO={$p->CDPROJETO}\n";
}

// 3. Patrimônios sem MARCA/MODELO
$patrimoniosSemMarca = Patrimonio::where(function($q) {
    $q->whereNull('MARCA')->orWhere('MARCA', '');
})->limit(50)->get();

echo "\n📋 SEM MARCA (primeiros 50):\n";
echo "Total: " . Patrimonio::where(function($q) {
    $q->whereNull('MARCA')->orWhere('MARCA', '');
})->count() . "\n";
foreach ($patrimoniosSemMarca as $p) {
    echo "  Patrimônio #{$p->NUPATRIMONIO}: MARCA='{$p->MARCA}', DEPATRIMONIO='{$p->DEPATRIMONIO}'\n";
}

echo "\n✅ Análise concluída\n";
