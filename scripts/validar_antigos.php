<?php
// one-off: script para extrair APENAS patrimônios ANTIGOS (antes de 2011) com discrepâncias

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Patrimonio;

echo "\n=== PATRIMÔNIOS ANTIGOS (ANTES DE 2011) COM DISCREPÂNCIAS ===\n\n";

// PRÉ-CARREGAR TODOS OS PATRIMÔNIOS
echo "📥 Carregando patrimônios do banco...\n";
$todosBanco = Patrimonio::all()->keyBy('NUPATRIMONIO');
echo "✅ " . count($todosBanco) . " patrimônios carregados\n\n";

// Ler arquivo PATRIMONIO.txt
$arquivo = file(base_path('PATRIMONIO.txt'));

echo "🔍 Lendo PATRIMONIO.txt e validando datas abaixo de 2011...\n\n";

$discrepancias = [];
$processadas = 0;
$naoEncontradas = 0;
$acima2011 = 0;

for ($i = 2; $i < count($arquivo); $i++) {
    $linha = trim($arquivo[$i]);
    if (empty($linha)) continue;

    // Split por espaços múltiplos
    $colunas = preg_split('/\s{2,}/', $linha, -1, PREG_SPLIT_NO_EMPTY);
    
    if (count($colunas) < 2) continue;

    $nupatrimonio = trim($colunas[0]);
    if (!is_numeric($nupatrimonio)) continue;

    $nupatrimonio = (int)$nupatrimonio;

    // DTAQUISICAO está na posição 6
    $dtAquisicaoTxt = isset($colunas[6]) ? trim($colunas[6]) : '';

    // Converter data de DD/MM/YYYY para Y-m-d
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dtAquisicaoTxt, $matches)) {
        $dtAquisicaoFormatada = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        $anoTxt = (int)$matches[3];
    } elseif ($dtAquisicaoTxt === '<null>' || $dtAquisicaoTxt === '') {
        $dtAquisicaoTxt = '<null>';
        $dtAquisicaoFormatada = null;
        $anoTxt = null;
    } else {
        continue;
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
    $anoBanco = $dtBanco ? (int)date('Y', strtotime($dtBanco)) : null;

    $processadas++;

    // FILTRO: Apenas patrimônios com ano <= 2010 (antes de 2011)
    $anoFiltro = $anoTxt ?? $anoBanco;
    if (!$anoFiltro || $anoFiltro > 2010) {
        if ($anoFiltro > 2010) {
            $acima2011++;
        }
        continue;
    }

    // Comparar
    if ($dtAquisicaoFormatada !== $dtBanco) {
        $discrepancias[] = [
            'NUPATRIMONIO' => $nupatrimonio,
            'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO,
            'DTAQUISICAO_TXT' => $dtAquisicaoTxt,
            'ANO_TXT' => $anoTxt ?? 'NULL',
            'DTAQUISICAO_BANCO' => $dtBancoFormatada,
            'ANO_BANCO' => $anoBanco ?? 'NULL',
            'LINHA' => $i + 1,
        ];
    }
}

echo "📊 RESUMO:\n";
echo "  • Linhas processadas: $processadas\n";
echo "  • Acima de 2011 (ignorados): $acima2011\n";
echo "  • Não encontradas: $naoEncontradas\n";
echo "  • Discrepâncias em ANTIGOS (<=2010): " . count($discrepancias) . "\n\n";

if (count($discrepancias) > 0) {
    echo "❌ PATRIMÔNIOS ANTIGOS COM DATA ERRADA:\n\n";
    echo "NUPATRIMONIO | DESCRIÇÃO (primeiros 30 chars) | DTAQUISICAO (TXT) | ANO TXT | DTAQUISICAO (BANCO) | ANO BANCO | LINHA\n";
    echo "========================================================================================================================================================\n";
    
    foreach ($discrepancias as $disc) {
        $desc = substr($disc['DEPATRIMONIO'], 0, 30);
        printf(
            "%10d | %-30s | %17s | %7s | %18s | %9s | %4d\n",
            $disc['NUPATRIMONIO'],
            $desc,
            $disc['DTAQUISICAO_TXT'],
            $disc['ANO_TXT'],
            $disc['DTAQUISICAO_BANCO'],
            $disc['ANO_BANCO'],
            $disc['LINHA']
        );
    }

    // Exportar para CSV
    $csvPath = storage_path('app/patrimonios_antigos_discrepancias.csv');
    $handle = fopen($csvPath, 'w');
    fputcsv($handle, ['NUPATRIMONIO', 'DEPATRIMONIO', 'DTAQUISICAO_TXT', 'ANO_TXT', 'DTAQUISICAO_BANCO', 'ANO_BANCO', 'LINHA']);
    
    foreach ($discrepancias as $disc) {
        fputcsv($handle, [
            $disc['NUPATRIMONIO'],
            $disc['DEPATRIMONIO'],
            $disc['DTAQUISICAO_TXT'],
            $disc['ANO_TXT'],
            $disc['DTAQUISICAO_BANCO'],
            $disc['ANO_BANCO'],
            $disc['LINHA'],
        ]);
    }
    fclose($handle);

    echo "\n📁 Arquivo exportado: storage/app/patrimonios_antigos_discrepancias.csv\n";
} else {
    echo "✅ Nenhuma discrepância encontrada em patrimônios antigos (<=2010)!\n";
}

echo "\n";
?>
