<?php
// one-off: script para validar datas do PATRIMONIO.txt contra banco de dados

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Patrimonio;

echo "\n=== VALIDAÇÃO DE DATAS: PATRIMONIO.txt vs BANCO DE DADOS ===\n\n";

// PRÉ-CARREGAR TODOS OS PATRIMÔNIOS NA MEMÓRIA
echo "📥 Carregando todos os patrimônios do banco...\n";
$todosBanco = Patrimonio::all()->keyBy('NUPATRIMONIO');
echo "✅ " . count($todosBanco) . " patrimônios carregados\n\n";

// Ler arquivo PATRIMONIO.txt
$arquivo = file(base_path('PATRIMONIO.txt'));

echo "🔍 Lendo arquivo PATRIMONIO.txt...\n";
echo "Total de linhas: " . count($arquivo) . "\n\n";

// Processar linhas
$discrepancias = [];
$processadas = 0;
$naoEncontradas = 0;

for ($i = 2; $i < count($arquivo); $i++) {
    $linha = trim($arquivo[$i]);
    if (empty($linha)) continue;

    // Split por espaços múltiplos (colunas fixas)
    $colunas = preg_split('/\s{2,}/', $linha, -1, PREG_SPLIT_NO_EMPTY);
    
    if (count($colunas) < 2) continue;

    $nupatrimonio = trim($colunas[0]);
    if (!is_numeric($nupatrimonio)) continue;

    $nupatrimonio = (int)$nupatrimonio;

    // DTAQUISICAO está na posição 6 (após NUPATRIMONIO, SITUACAO, MARCA, CDLOCAL, MODELO, COR)
    $dtAquisicaoTxt = isset($colunas[6]) ? trim($colunas[6]) : '';

    // Converter data de DD/MM/YYYY para Y-m-d
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dtAquisicaoTxt, $matches)) {
        $dtAquisicaoFormatada = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
    } elseif ($dtAquisicaoTxt === '<null>' || $dtAquisicaoTxt === '') {
        $dtAquisicaoTxt = '<null>';
        $dtAquisicaoFormatada = null;
    } else {
        continue; // Data em formato inválido, pular
    }

    // Buscar no banco
    $patrimonio = $todosBanco[$nupatrimonio] ?? null;
    
    if (!$patrimonio) {
        $naoEncontradas++;
        continue;
    }

    $dtBancoBruta = $patrimonio->DTAQUISICAO;
    $dtBanco = $dtBancoBruta ? date('Y-m-d', strtotime($dtBancoBruta)) : null;
    $dtBancoFormatada = $dtBanco ? date('d/m/Y', strtotime($dtBanco)) : '<null>';

    $processadas++;

    // Comparar
    if ($dtAquisicaoFormatada !== $dtBanco) {
        $discrepancias[] = [
            'NUPATRIMONIO' => $nupatrimonio,
            'DTAQUISICAO_TXT' => $dtAquisicaoTxt,
            'DTAQUISICAO_BANCO' => $dtBancoFormatada,
            'LINHA' => $i + 1,
            'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO,
        ];
    }
}

echo "📊 RESUMO DA VALIDAÇÃO:\n";
echo "  • Linhas processadas: $processadas\n";
echo "  • Não encontradas no banco: $naoEncontradas\n";
echo "  • Discrepâncias encontradas: " . count($discrepancias) . "\n\n";

if (count($discrepancias) > 0) {
    echo "❌ DISCREPÂNCIAS DETECTADAS:\n\n";
    echo "NUPATRIMONIO | DESCRIÇÃO | DTAQUISICAO (TXT) | DTAQUISICAO (BANCO) | LINHA\n";
    echo "===================================================================\n";
    
    foreach (array_slice($discrepancias, 0, 30) as $disc) {
        $desc = substr($disc['DEPATRIMONIO'], 0, 25);
        echo sprintf(
            "%10d | %-25s | %17s | %18s | %4d\n",
            $disc['NUPATRIMONIO'],
            $desc,
            $disc['DTAQUISICAO_TXT'],
            $disc['DTAQUISICAO_BANCO'],
            $disc['LINHA']
        );
    }

    if (count($discrepancias) > 30) {
        echo "... e mais " . (count($discrepancias) - 30) . " discrepâncias\n";
    }

    // Exportar relatório
    $reportPath = storage_path('app/validacao_datas_patrimonio.csv');
    $handle = fopen($reportPath, 'w');
    fputcsv($handle, ['NUPATRIMONIO', 'DEPATRIMONIO', 'DTAQUISICAO_TXT', 'DTAQUISICAO_BANCO', 'LINHA']);
    
    foreach ($discrepancias as $disc) {
        fputcsv($handle, [
            $disc['NUPATRIMONIO'],
            $disc['DEPATRIMONIO'],
            $disc['DTAQUISICAO_TXT'],
            $disc['DTAQUISICAO_BANCO'],
            $disc['LINHA'],
        ]);
    }
    fclose($handle);

    echo "\n📁 Relatório exportado: storage/app/validacao_datas_patrimonio.csv\n";
} else {
    echo "✅ Todas as datas estão corretas e sincronizadas!\n";
}

echo "\n";
?>
