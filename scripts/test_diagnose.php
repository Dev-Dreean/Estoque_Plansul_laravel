<?php
// Script de diagnóstico para investigar patrimônios específicos e paginação

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\ObjetoPatr;

echo "=== DIAGNÓSTICO DE PATRIMÔNIOS E PAGINAÇÃO ===\n\n";

// 1. Buscar os três patrimônios específicos
$buscados = [17483, 6817, 22502];
echo "[1] Buscando patrimônios específicos:\n";
foreach ($buscados as $num) {
    $p = Patrimonio::where('NUPATRIMONIO', $num)->first();
    if ($p) {
        echo "✓ Patrimônio $num encontrado:\n";
        echo "  - NUSEQPATR: {$p->NUSEQPATR}\n";
        echo "  - DEPATRIMONIO: " . ($p->DEPATRIMONIO ?? 'NULL') . "\n";
        echo "  - MODELO: " . ($p->MODELO ?? 'NULL') . "\n";
        echo "  - MARCA: " . ($p->MARCA ?? 'NULL') . "\n";
        echo "  - NMPLANTA: " . ($p->NMPLANTA ?? 'NULL') . "\n";
        echo "  - CODOBJETO: " . ($p->CODOBJETO ?? 'NULL') . "\n";
        if ($p->CODOBJETO) {
            $obj = ObjetoPatr::find($p->CODOBJETO);
            if ($obj) {
                echo "  - DEOBJETO (from CODOBJETO): " . ($obj->DEOBJETO ?? 'NULL') . "\n";
            }
        }
    } else {
        echo "✗ Patrimônio $num NÃO encontrado\n";
    }
    echo "\n";
}

// 2. Contagem por status
echo "[2] Contagens por status:\n";
$totalCadastrados = Patrimonio::count();
$semDescricao = Patrimonio::whereNull('DEPATRIMONIO')->orWhere('DEPATRIMONIO', '')->count();
$comDescricao = Patrimonio::whereNotNull('DEPATRIMONIO')->where('DEPATRIMONIO', '<>', '')->count();
$semNMPLANTA = Patrimonio::whereNull('NMPLANTA')->count();
$comNMPLANTA = Patrimonio::whereNotNull('NMPLANTA')->count();

echo "  - Total cadastrados: $totalCadastrados\n";
echo "  - Sem DEPATRIMONIO (NULL ou vazio): $semDescricao\n";
echo "  - Com DEPATRIMONIO preenchido: $comDescricao\n";
echo "  - Sem NMPLANTA (disponível para atribuição): $semNMPLANTA\n";
echo "  - Com NMPLANTA (já atribuído): $comNMPLANTA\n";

// 3. Verificar paginação padrão (15 por página)
echo "\n[3] Teste de paginação (padrão: 15 por página):\n";
$paginator = Patrimonio::whereNull('NMPLANTA')
    ->orderBy('NMPLANTA', 'asc')
    ->orderBy('NUPATRIMONIO', 'asc')
    ->paginate(15);

echo "  - Per page: {$paginator->perPage()}\n";
echo "  - Total items: {$paginator->total()}\n";
echo "  - Últimas páginas: {$paginator->lastPage()}\n";
echo "  - Itens nesta página: " . count($paginator->items()) . "\n";
echo "  - Página atual: {$paginator->currentPage()}\n";

// 4. Listar primeiros itens da página 1 (sem filtro)
echo "\n[4] Primeiros 10 itens da página 1 (sem DEPATRIMONIO filter):\n";
$sample = Patrimonio::whereNull('NMPLANTA')
    ->orderBy('NMPLANTA', 'asc')
    ->orderBy('NUPATRIMONIO', 'asc')
    ->limit(10)
    ->get(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'MODELO', 'MARCA', 'CODOBJETO']);

foreach ($sample as $p) {
    $desc = $p->DEPATRIMONIO ?? '(vazio)';
    echo "  - #{$p->NUPATRIMONIO} | {$desc} | Modelo: {$p->MODELO} | Marca: {$p->MARCA}\n";
}

// 5. Verificar se há descrições em ObjetoPatr para os sem DEPATRIMONIO
echo "\n[5] Amostra de patrimônios SEM DEPATRIMONIO (verificar se há CODOBJETO):\n";
$semDesc = Patrimonio::whereNull('DEPATRIMONIO')
    ->orWhere('DEPATRIMONIO', '')
    ->orderBy('NUPATRIMONIO')
    ->limit(5)
    ->get(['NUSEQPATR', 'NUPATRIMONIO', 'CODOBJETO', 'MODELO', 'MARCA']);

foreach ($semDesc as $p) {
    $deobjeto = '';
    if ($p->CODOBJETO) {
        $obj = ObjetoPatr::find($p->CODOBJETO);
        $deobjeto = $obj ? $obj->DEOBJETO : '(objeto não encontrado)';
    } else {
        $deobjeto = '(sem CODOBJETO)';
    }
    echo "  - #{$p->NUPATRIMONIO} | CODOBJETO: {$p->CODOBJETO} | DEOBJETO: {$deobjeto}\n";
}

echo "\nDiagnóstico concluído.\n";
