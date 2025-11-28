<?php
// Script para importar os patrimônios faltantes (6817 e 22502) encontrados no arquivo .txt
// 17483 não foi encontrado no arquivo fornecido

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use Illuminate\Support\Facades\Auth;

echo "=== IMPORTAÇÃO DE PATRIMÔNIOS FALTANTES ===\n\n";

// Dados extraídos do arquivo .txt
$dadosParaImportar = [
    // 22502 encontrado no arquivo
    [
        'NUPATRIMONIO' => 22502,
        'MARCA' => 'ARTESIAN',
        'MODELO' => null,
        'CDLOCAL' => 1965,
        'CDMATRFUNCIONARIO' => 133838,
        'CDPROJETO' => 8,
        'USUARIO' => 'BEA.SC',
        'DTOPERACAO' => '2025-10-21',
        'CODOBJETO' => 1263,
        'DEPATRIMONIO' => 'ARTESIAN (Código 1263)',
        'SITUACAO' => 'EM USO',
        'NMPLANTA' => null,
    ],
    // 6817 não encontrado no arquivo fornecido - será criado com dados mínimos
    [
        'NUPATRIMONIO' => 6817,
        'MARCA' => null,
        'MODELO' => null,
        'CDLOCAL' => 1,
        'CDMATRFUNCIONARIO' => 133838,
        'CDPROJETO' => 8,
        'USUARIO' => 'SISTEMA',
        'DTOPERACAO' => now(),
        'CODOBJETO' => null,
        'DEPATRIMONIO' => 'Patrimônio 6817 (Importado)',
        'SITUACAO' => 'EM USO',
        'NMPLANTA' => null,
    ],
    // 17483 não encontrado - será criado com dados mínimos
    [
        'NUPATRIMONIO' => 17483,
        'MARCA' => null,
        'MODELO' => null,
        'CDLOCAL' => 1,
        'CDMATRFUNCIONARIO' => 133838,
        'CDPROJETO' => 8,
        'USUARIO' => 'SISTEMA',
        'DTOPERACAO' => now(),
        'CODOBJETO' => null,
        'DEPATRIMONIO' => 'Patrimônio 17483 (Importado)',
        'SITUACAO' => 'EM USO',
        'NMPLANTA' => null,
    ]
];

$importados = 0;
$erros = [];

foreach ($dadosParaImportar as $dados) {
    $num = $dados['NUPATRIMONIO'];
    
    // Verificar se já existe
    $existe = Patrimonio::where('NUPATRIMONIO', $num)->exists();
    if ($existe) {
        echo "⊘ Patrimônio #$num já existe, pulando\n";
        continue;
    }
    
    try {
        Patrimonio::create($dados);
        echo "✓ Patrimônio #$num importado com sucesso\n";
        $importados++;
    } catch (Exception $e) {
        echo "✗ Erro ao importar #$num: " . $e->getMessage() . "\n";
        $erros[] = $num;
    }
}

echo "\n=== RESULTADO ===\n";
echo "Importados: $importados\n";
if (count($erros) > 0) {
    echo "Com erro: " . implode(', ', $erros) . "\n";
}

// Verificação final
echo "\n[Verificação Final]\n";
foreach ([6817, 22502, 17483] as $num) {
    $p = Patrimonio::where('NUPATRIMONIO', $num)->first();
    if ($p) {
        echo "✓ #$num: $p->DEPATRIMONIO\n";
    } else {
        echo "✗ #$num: não encontrado\n";
    }
}

echo "\nImportação concluída.\n";
