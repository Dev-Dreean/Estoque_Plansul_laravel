<?php
// Script de correção: popular DEPATRIMONIO a partir de ObjetoPatr e importar patrimônios faltantes

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\ObjetoPatr;
use Illuminate\Support\Facades\DB;

echo "=== SCRIPT DE CORREÇÃO DE PATRIMÔNIOS ===\n\n";

// PARTE 1: Popular DEPATRIMONIO a partir de CODOBJETO
echo "[1] Populando DEPATRIMONIO a partir de ObjetoPatr...\n";

$patrimoniosSemDesc = Patrimonio::where(function($q) {
    $q->whereNull('DEPATRIMONIO')->orWhere('DEPATRIMONIO', '');
})->get();

$atualizados = 0;
foreach ($patrimoniosSemDesc as $p) {
    if ($p->CODOBJETO) {
        $objeto = ObjetoPatr::find($p->CODOBJETO);
        if ($objeto && $objeto->DEOBJETO) {
            $p->update(['DEPATRIMONIO' => $objeto->DEOBJETO]);
            $atualizados++;
        }
    }
}

echo "✓ $atualizados patrimônios atualizados com descrição de ObjetoPatr\n\n";

// PARTE 2: Verificar se os patrimônios 17483, 6817, 22502 existem
echo "[2] Verificando patrimônios específicos (17483, 6817, 22502):\n";
$faltantes = [];
foreach ([17483, 6817, 22502] as $num) {
    $existe = Patrimonio::where('NUPATRIMONIO', $num)->exists();
    if (!$existe) {
        echo "  - #$num: FALTANDO\n";
        $faltantes[] = $num;
    } else {
        echo "  - #$num: existe\n";
    }
}

if (count($faltantes) > 0) {
    echo "\n⚠️  Patrimônios faltantes: " . implode(', ', $faltantes) . "\n";
    echo "Estes patrimônios precisam ser importados do arquivo .txt\n";
    echo "Você pode:\n";
    echo "  1. Fornecer os dados em formato JSON/array\n";
    echo "  2. Usar um script de importação SQL\n";
    echo "  3. Cadastrá-los manualmente pela interface\n";
}

echo "\n[3] Resumo final:\n";
$totalAgora = Patrimonio::count();
$comDescricao = Patrimonio::whereNotNull('DEPATRIMONIO')->where('DEPATRIMONIO', '<>', '')->count();
echo "  - Total de patrimônios: $totalAgora\n";
echo "  - Com descrição preenchida: $comDescricao\n";
echo "  - Taxa de cobertura: " . round(($comDescricao / $totalAgora) * 100, 1) . "%\n";

echo "\nScript concluído.\n";
