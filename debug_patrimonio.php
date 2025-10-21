<?php
// Debug script para verificar patrimônios

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Patrimonio;
use App\Services\FilterService;

// Verificar quantos patrimônios existem
$total = Patrimonio::count();
echo "Total de patrimônios: $total\n";

if ($total > 0) {
    // Pegar alguns registros
    $patrimonios = Patrimonio::select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'SITUACAO'])
        ->limit(5)
        ->get()
        ->toArray();

    echo "Primeiros 5 patrimônios:\n";
    echo json_encode($patrimonios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    // Testar FilterService
    echo "\n=== Testando FilterService ===\n";
    $todos = Patrimonio::select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'SITUACAO'])
        ->get()
        ->toArray();

    echo "Total carregado: " . count($todos) . "\n";

    // Teste 1: Buscar por "1"
    $resultado = FilterService::filtrar(
        $todos,
        '1',
        ['NUPATRIMONIO', 'DEPATRIMONIO'],
        ['NUPATRIMONIO' => 'número', 'DEPATRIMONIO' => 'texto'],
        10
    );

    echo "\nBusca por '1': " . count($resultado) . " resultados\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "AVISO: Nenhum patrimônio encontrado no banco de dados!\n";
}
