<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📊 VALIDAÇÃO BANCO DE DADOS LOCAL:\n";
echo str_repeat("═", 80) . "\n\n";

try {
    // Conectar ao banco
    $total = App\Models\Patrimonio::count();
    echo "✅ Conexão com banco: OK\n";
    echo "✅ Total de patrimônios: $total\n\n";

    // Validar descrições
    $comDesc = App\Models\Patrimonio::whereNotNull('DEPATRIMONIO')
        ->where('DEPATRIMONIO', '!=', '')
        ->count();
    $semDesc = $total - $comDesc;
    
    echo "📋 DESCRIÇÕES:\n";
    echo "  ✅ Com descrição: $comDesc (" . round(($comDesc/$total)*100, 2) . "%)\n";
    echo "  ❌ Sem descrição: $semDesc\n\n";

    // Validar marca
    $comMarca = App\Models\Patrimonio::whereNotNull('MARCA')
        ->where('MARCA', '!=', '')
        ->count();
    echo "🏷️  MARCA:\n";
    echo "  ✅ Com marca: $comMarca (" . round(($comMarca/$total)*100, 2) . "%)\n\n";

    // Validar modelo
    $comModelo = App\Models\Patrimonio::whereNotNull('MODELO')
        ->where('MODELO', '!=', '')
        ->count();
    echo "🔧 MODELO:\n";
    echo "  ✅ Com modelo: $comModelo (" . round(($comModelo/$total)*100, 2) . "%)\n\n";

    // Amostra
    echo "📌 AMOSTRA PATRIMÔNIOS #1-10:\n";
    $amostra = App\Models\Patrimonio::whereIn('NUPATRIMONIO', range(1, 10))
        ->orderBy('NUPATRIMONIO')
        ->get(['NUPATRIMONIO', 'DEPATRIMONIO', 'MARCA', 'MODELO']);
    
    foreach($amostra as $p) {
        $desc = substr($p->DEPATRIMONIO ?? '-', 0, 30);
        $marca = substr($p->MARCA ?? '-', 0, 15);
        $modelo = substr($p->MODELO ?? '-', 0, 15);
        echo sprintf("  #%-3d | %-30s | %-15s | %-15s\n", 
            $p->NUPATRIMONIO, $desc, $marca, $modelo);
    }

    echo "\n" . str_repeat("═", 80) . "\n";
    echo "✅ BANCO DE DADOS: VALIDADO COM SUCESSO\n";
    echo str_repeat("═", 80) . "\n";

} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
