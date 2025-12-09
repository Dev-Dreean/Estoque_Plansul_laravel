<?php
// one-off: script para extrair patrimonios cadastrados ANTES de 2011

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Patrimonio;
use Illuminate\Support\Facades\Log;

echo "\n=== EXTRAINDO PATRIMÔNIOS CADASTRADOS ANTES DE 2011 (ATÉ 2010) ===\n\n";

try {
    echo "🔍 Buscando patrimônios com DTAQUISICAO até 2010...\n";
    
    // Extrair todos os patrimônios com DTAQUISICAO até 31/12/2010
    $patrimonios = Patrimonio::whereRaw('YEAR(DTAQUISICAO) <= 2010')
        ->orderBy('DTAQUISICAO', 'asc')
        ->get([
            'NUSEQPATR',
            'NUPATRIMONIO',
            'CODOBJETO',
            'DEPATRIMONIO',
            'MARCA',
            'MODELO',
            'SITUACAO',
            'CDLOCAL',
            'CDPROJETO',
            'CDMATRFUNCIONARIO',
            'DTAQUISICAO',
            'DTOPERACAO',
        ]);

    $total = $patrimonios->count();
    echo "✅ Total de patrimônios encontrados (até 2010): $total\n\n";

    if ($total == 0) {
        echo "⚠️ Nenhum patrimônio encontrado com DTAQUISICAO <= 2010\n";
        echo "Vou listar patrimônios com DTAQUISICAO mais antiga:\n\n";
        
        $antigos = Patrimonio::orderBy('DTAQUISICAO', 'asc')
            ->limit(30)
            ->get(['NUPATRIMONIO', 'DEPATRIMONIO', 'DTAQUISICAO']);
        
        foreach ($antigos as $p) {
            $data = $p->DTAQUISICAO ? date('Y-m-d', strtotime($p->DTAQUISICAO)) : 'NULL';
            echo "  • Pat #" . $p->NUPATRIMONIO . " (" . $data . "): " . substr($p->DEPATRIMONIO, 0, 50) . "\n";
        }
        exit(1);
    }

    // Agrupar por ano
    $porAno = $patrimonios->groupBy(function($item) {
        return date('Y', strtotime($item->DTAQUISICAO));
    });

    echo "📊 DISTRIBUIÇÃO POR ANO (2010 e anteriores):\n";
    foreach ($porAno as $ano => $registros) {
        echo "  • $ano: " . count($registros) . " patrimônios\n";
    }

    // Análise de campos vazios
    echo "\n❌ ANÁLISE DE CAMPOS VAZIOS:\n";
    $semMarca = $patrimonios->where('MARCA', null)->count();
    $semModelo = $patrimonios->where('MODELO', null)->count();
    $semDescricao = $patrimonios->where('DEPATRIMONIO', null)->count();
    $semSituacao = $patrimonios->where('SITUACAO', null)->count();
    $semLocal = $patrimonios->where('CDLOCAL', null)->count();

    if ($total > 0) {
        echo "  • Sem MARCA: $semMarca (" . round(($semMarca/$total)*100, 2) . "%)\n";
        echo "  • Sem MODELO: $semModelo (" . round(($semModelo/$total)*100, 2) . "%)\n";
        echo "  • Sem DESCRIÇÃO: $semDescricao (" . round(($semDescricao/$total)*100, 2) . "%)\n";
        echo "  • Sem SITUAÇÃO: $semSituacao (" . round(($semSituacao/$total)*100, 2) . "%)\n";
        echo "  • Sem LOCAL: $semLocal (" . round(($semLocal/$total)*100, 2) . "%)\n";
    }

    // Exportar para arquivo CSV
    $csvPath = storage_path('app/patrimonios_ate_2011.csv');
    $handle = fopen($csvPath, 'w');

    // Cabeçalho
    fputcsv($handle, [
        'NUSEQPATR',
        'NUPATRIMONIO',
        'CODOBJETO',
        'DEPATRIMONIO',
        'MARCA',
        'MODELO',
        'SITUACAO',
        'CDLOCAL',
        'CDPROJETO',
        'CDMATRFUNCIONARIO',
        'DTAQUISICAO',
        'DTOPERACAO',
    ]);

    // Dados
    foreach ($patrimonios as $p) {
        fputcsv($handle, [
            $p->NUSEQPATR,
            $p->NUPATRIMONIO,
            $p->CODOBJETO,
            $p->DEPATRIMONIO,
            $p->MARCA,
            $p->MODELO,
            $p->SITUACAO,
            $p->CDLOCAL,
            $p->CDPROJETO,
            $p->CDMATRFUNCIONARIO,
            $p->DTAQUISICAO ? date('Y-m-d', strtotime($p->DTAQUISICAO)) : '',
            $p->DTOPERACAO ? date('Y-m-d', strtotime($p->DTOPERACAO)) : '',
        ]);
    }
    fclose($handle);

    echo "\n📁 Arquivo exportado: storage/app/patrimonios_ate_2011.csv\n";

    // Exportar também em JSON para facilitar processamento
    $jsonPath = storage_path('app/patrimonios_ate_2011.json');
    file_put_contents($jsonPath, json_encode($patrimonios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "📁 Arquivo exportado: storage/app/patrimonios_ate_2011.json\n";

    // Log da operação
    Log::info('Patrimonios ate 2010 extraidos com sucesso', [
        'total' => $total,
        'sem_marca' => $semMarca,
        'sem_modelo' => $semModelo,
        'sem_descricao' => $semDescricao,
        'sem_situacao' => $semSituacao,
        'sem_local' => $semLocal,
    ]);

    echo "\n✅ Extração concluída com sucesso!\n";
    echo "💡 Abra os arquivos em storage/app/ para revisar e ajustar os cadastros.\n\n";

} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    Log::error('Erro ao extrair patrimonios ate 2010', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
?>
