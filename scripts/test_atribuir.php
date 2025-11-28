<?php
// script temporário para testar listagem de patrimônios para atribuição
// roda no contexto do framework Laravel

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap minimal do kernel console
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use Illuminate\Support\Facades\Auth;

echo "Testando listagens de atribuição\n";

try {
    // 1) Simular comportamento de PatrimonioController::atribuir
    $query1 = Patrimonio::query();
    // Não aplicar filtro por usuário (deve listar todos)
    // Filtro status 'disponivel' => NMPLANTA is null
    $status = 'disponivel';
    if ($status === 'disponivel') {
        $query1->whereNull('NMPLANTA');
    }
    $query1->whereNotNull('DEPATRIMONIO')->where('DEPATRIMONIO', '<>', '');
    $count1 = $query1->count();

    $sample1Coll = $query1->orderBy('NUPATRIMONIO')->limit(10)->get(['NUSEQPATR','NUPATRIMONIO','DEPATRIMONIO','MODELO','MARCA','NUSERIE','COR','CODOBJETO','NMPLANTA']);
    // preencher descricoes a partir de ObjetoPatr
    $codes1 = $sample1Coll->pluck('CODOBJETO')->filter()->unique()->values()->all();
    $descMap1 = [];
    if (!empty($codes1)) {
        $descMap1 = \App\Models\ObjetoPatr::whereIn('NUSEQOBJETO', $codes1)->pluck('DEOBJETO', 'NUSEQOBJETO')->toArray();
    }
    $sample1 = $sample1Coll->map(function($p) use ($descMap1) {
        $display = $p->DEPATRIMONIO ?: ($descMap1[$p->CODOBJETO] ?? null);
        if (empty($display)) {
            $parts = array_filter([$p->MODELO ?? null, $p->MARCA ?? null, $p->NUSERIE ?? null, $p->COR ?? null]);
            $display = $parts ? implode(' - ', $parts) : 'SEM DESCRIÇÃO';
        }
        return [
            'NUSEQPATR' => $p->NUSEQPATR,
            'NUPATRIMONIO' => $p->NUPATRIMONIO,
            'DESCRICAO_EXIBIR' => $display,
            'NMPLANTA' => $p->NMPLANTA,
        ];
    })->toArray();

    echo "\n[atribuir] total disponiveis (NMPLANTA NULL & DEPATRIMONIO presente): $count1\n";
    echo json_encode($sample1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    // 2) Simular comportamento de PatrimonioController::atribuirCodigos (similar)
    $query2 = Patrimonio::query();
    $query2->whereNull('NMPLANTA');
    $count2 = $query2->count();
    $sample2Coll = $query2->orderBy('NUPATRIMONIO')->limit(10)->get(['NUSEQPATR','NUPATRIMONIO','DEPATRIMONIO','MODELO','MARCA','NUSERIE','COR','CODOBJETO','NMPLANTA']);
    $codes2 = $sample2Coll->pluck('CODOBJETO')->filter()->unique()->values()->all();
    $descMap2 = [];
    if (!empty($codes2)) {
        $descMap2 = \App\Models\ObjetoPatr::whereIn('NUSEQOBJETO', $codes2)->pluck('DEOBJETO', 'NUSEQOBJETO')->toArray();
    }
    $sample2 = $sample2Coll->map(function($p) use ($descMap2) {
        $display = $p->DEPATRIMONIO ?: ($descMap2[$p->CODOBJETO] ?? null);
        if (empty($display)) {
            $parts = array_filter([$p->MODELO ?? null, $p->MARCA ?? null, $p->NUSERIE ?? null, $p->COR ?? null]);
            $display = $parts ? implode(' - ', $parts) : 'SEM DESCRIÇÃO';
        }
        return [
            'NUSEQPATR' => $p->NUSEQPATR,
            'NUPATRIMONIO' => $p->NUPATRIMONIO,
            'DESCRICAO_EXIBIR' => $display,
            'NMPLANTA' => $p->NMPLANTA,
        ];
    })->toArray();
    echo "\n[atribuirCodigos] total disponiveis (NMPLANTA NULL): $count2\n";
    echo json_encode($sample2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    // 3) Simular getPatrimoniosDisponiveis (api) - original tinha orWhere('NMPLANTA', '')
    $query3 = Patrimonio::query();
    $query3->where(function($q){
        $q->whereNull('NMPLANTA')->orWhere('NMPLANTA','');
    });
    $count3 = $query3->count();
    $sample3Coll = $query3->orderBy('NUPATRIMONIO')->limit(10)->get(['NUSEQPATR','NUPATRIMONIO','DEPATRIMONIO','MODELO','MARCA','NUSERIE','COR','CODOBJETO','NMPLANTA']);
    $codes3 = $sample3Coll->pluck('CODOBJETO')->filter()->unique()->values()->all();
    $descMap3 = [];
    if (!empty($codes3)) {
        $descMap3 = \App\Models\ObjetoPatr::whereIn('NUSEQOBJETO', $codes3)->pluck('DEOBJETO', 'NUSEQOBJETO')->toArray();
    }
    $sample3 = $sample3Coll->map(function($p) use ($descMap3) {
        $display = $p->DEPATRIMONIO ?: ($descMap3[$p->CODOBJETO] ?? null);
        if (empty($display)) {
            $parts = array_filter([$p->MODELO ?? null, $p->MARCA ?? null, $p->NUSERIE ?? null, $p->COR ?? null]);
            $display = $parts ? implode(' - ', $parts) : 'SEM DESCRIÇÃO';
        }
        return [
            'NUSEQPATR' => $p->NUSEQPATR,
            'NUPATRIMONIO' => $p->NUPATRIMONIO,
            'DESCRICAO_EXIBIR' => $display,
            'NMPLANTA' => $p->NMPLANTA,
        ];
    })->toArray();
    echo "\n[getPatrimoniosDisponiveis] total disponiveis (NMPLANTA NULL or empty): $count3\n";
    echo json_encode($sample3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    // 4) Contagem total geral de patrimônios cadastrados
    $total = Patrimonio::count();
    echo "\n[geral] total de patrimônios cadastrados: $total\n";

    echo "\nTeste concluído.\n";
} catch (Throwable $e) {
    echo "Erro ao executar script: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

