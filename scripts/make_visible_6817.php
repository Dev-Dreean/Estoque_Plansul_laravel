<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\ObjetoPatr;
use Illuminate\Support\Facades\DB;

echo "=== TORNAR PATRIMONIO 6817 VISIVEL A TODOS ===\n";

$nu = 6817;

DB::beginTransaction();
try {
    $p = Patrimonio::where('NUPATRIMONIO', $nu)->first();
    if (!$p) {
        echo "❌ Patrimônio $nu não encontrado.\n";
        exit(1);
    }

    echo "Encontrado: NUSEQPATR={$p->NUSEQPATR} NUPATRIMONIO={$p->NUPATRIMONIO}\n";

    // Remover NMPLANTA para garantir que apareça nas listas de disponibilidade
    $p->NMPLANTA = null;

    // Tornar CDMATRFUNCIONARIO nulo (não vinculado a usuário específico)
    $p->CDMATRFUNCIONARIO = null;

    // Garantir DEPATRIMONIO preenchido
    if (empty($p->DEPATRIMONIO) && !empty($p->CODOBJETO)) {
        $obj = ObjetoPatr::where('NUSEQOBJETO', $p->CODOBJETO)->first();
        if ($obj) {
            $p->DEPATRIMONIO = $obj->DEOBJETO;
            echo "DEPATRIMONIO preenchido a partir do objeto: {$p->DEPATRIMONIO}\n";
        }
    }

    if (empty($p->DEPATRIMONIO)) {
        $parts = array_filter([$p->MODELO ?? null, $p->MARCA ?? null, $p->NUSERIE ?? null, $p->COR ?? null]);
        if ($parts) {
            $p->DEPATRIMONIO = implode(' - ', $parts);
            echo "DEPATRIMONIO gerado: {$p->DEPATRIMONIO}\n";
        }
    }

    $p->save();

    DB::commit();

    echo "✅ Atualização concluída — patrimônio $nu agora deve aparecer para todos.\n";
    echo "Sugestão: limpar caches no servidor:\n  php artisan cache:clear && php artisan config:clear\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

?>
