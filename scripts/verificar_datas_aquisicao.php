<?php
/**
 * one-off: Verificar se datas de aquisição foram importadas corretamente
 * Compara PATRIMONIO.TXT (fonte) com banco de dados atual
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║   ANÁLISE: Datas de Aquisição - TXT vs Banco de Dados          ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// Dados do TXT anexado (primeiros registros para análise)
$dadosTxt = [
    ['NUPATRIMONIO' => '5640', 'DTAQUISICAO' => '18/07/2014'],
    ['NUPATRIMONIO' => '5679', 'DTAQUISICAO' => '18/07/2014'],
    ['NUPATRIMONIO' => '5746', 'DTAQUISICAO' => '18/07/2014'],
    ['NUPATRIMONIO' => '5747', 'DTAQUISICAO' => '18/07/2014'],
    ['NUPATRIMONIO' => '456', 'DTAQUISICAO' => '17/07/2014'],
    ['NUPATRIMONIO' => '1', 'DTAQUISICAO' => '11/12/2011'],
    ['NUPATRIMONIO' => '2', 'DTAQUISICAO' => '11/12/2011'],
    ['NUPATRIMONIO' => '3', 'DTAQUISICAO' => null], // <null> no TXT
    ['NUPATRIMONIO' => '4', 'DTAQUISICAO' => '11/12/2011'],
    ['NUPATRIMONIO' => '7', 'DTAQUISICAO' => '11/12/2011'],
    ['NUPATRIMONIO' => '9', 'DTAQUISICAO' => '11/12/2011'],
    ['NUPATRIMONIO' => '38', 'DTAQUISICAO' => '11/12/2011'],
    ['NUPATRIMONIO' => '45', 'DTAQUISICAO' => '11/12/2011'],
    ['NUPATRIMONIO' => '62', 'DTAQUISICAO' => '31/12/1899'], // Data estranha
    ['NUPATRIMONIO' => '69', 'DTAQUISICAO' => '27/02/1900'], // Data estranha
];

echo "📊 Total de registros para análise: " . count($dadosTxt) . "\n\n";

// Buscar no banco de dados
$divergencias = [];
$corretos = [];
$nulos = [];

foreach ($dadosTxt as $item) {
    $nupatrimonio = $item['NUPATRIMONIO'];
    $dataEsperada = $item['DTAQUISICAO'];
    
    // Buscar no banco
    $patrimonio = DB::table('patr')
        ->where('NUPATRIMONIO', $nupatrimonio)
        ->first();
    
    if (!$patrimonio) {
        echo "⚠️  Patrimônio {$nupatrimonio}: NÃO ENCONTRADO NO BANCO\n";
        continue;
    }
    
    $dataAtual = $patrimonio->DTAQUISICAO;
    
    // Converter data do TXT para formato comparável
    $dataEsperadaFormatada = null;
    if ($dataEsperada) {
        try {
            $dt = DateTime::createFromFormat('d/m/Y', $dataEsperada);
            if ($dt) {
                $dataEsperadaFormatada = $dt->format('Y-m-d');
            }
        } catch (Exception $e) {
            // Ignorar erros de conversão
        }
    }
    
    // Comparar
    if ($dataEsperada === null && $dataAtual === null) {
        $nulos[] = [
            'patrimonio' => $nupatrimonio,
            'status' => 'Ambos nulos (OK)'
        ];
    } elseif ($dataEsperadaFormatada === $dataAtual) {
        $corretos[] = [
            'patrimonio' => $nupatrimonio,
            'data' => $dataAtual
        ];
    } else {
        $divergencias[] = [
            'patrimonio' => $nupatrimonio,
            'esperada_txt' => $dataEsperada,
            'esperada_formatada' => $dataEsperadaFormatada,
            'atual_banco' => $dataAtual
        ];
    }
}

// Exibir resultados
echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                      RESULTADO DA ANÁLISE                        ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ CORRETOS: " . count($corretos) . " registros com datas batendo\n";
foreach ($corretos as $item) {
    echo "   Patrimônio {$item['patrimonio']}: {$item['data']}\n";
}

echo "\n🔍 NULOS: " . count($nulos) . " registros sem data em ambos (esperado)\n";
foreach ($nulos as $item) {
    echo "   Patrimônio {$item['patrimonio']}: {$item['status']}\n";
}

echo "\n❌ DIVERGÊNCIAS: " . count($divergencias) . " registros com datas DIFERENTES\n";
if (count($divergencias) > 0) {
    echo "┌────────────┬──────────────────┬──────────────────┬──────────────────┐\n";
    echo "│ Patrimônio │ TXT Original     │ TXT Formatada    │ Banco Atual      │\n";
    echo "├────────────┼──────────────────┼──────────────────┼──────────────────┤\n";
    foreach ($divergencias as $div) {
        printf(
            "│ %-10s │ %-16s │ %-16s │ %-16s │\n",
            $div['patrimonio'],
            $div['esperada_txt'] ?? 'null',
            $div['esperada_formatada'] ?? 'null',
            $div['atual_banco'] ?? 'null'
        );
    }
    echo "└────────────┴──────────────────┴──────────────────┴──────────────────┘\n";
}

// Análise geral do banco
echo "\n\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║              ANÁLISE GERAL DO BANCO DE DADOS                     ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$totalPatrimonios = DB::table('patr')->count();
$comData = DB::table('patr')->whereNotNull('DTAQUISICAO')->count();
$semData = DB::table('patr')->whereNull('DTAQUISICAO')->count();

echo "📈 Total de Patrimônios: {$totalPatrimonios}\n";
echo "📅 Com Data de Aquisição: {$comData} (" . round(($comData/$totalPatrimonios)*100, 2) . "%)\n";
echo "⚪ Sem Data de Aquisição: {$semData} (" . round(($semData/$totalPatrimonios)*100, 2) . "%)\n";

// Amostra de datas no banco
echo "\n🔍 AMOSTRA DE DATAS NO BANCO (10 primeiros com data):\n";
$amostra = DB::table('patr')
    ->select('NUPATRIMONIO', 'DTAQUISICAO', 'DEPATRIMONIO')
    ->whereNotNull('DTAQUISICAO')
    ->orderBy('NUPATRIMONIO')
    ->limit(10)
    ->get();

echo "┌────────────┬──────────────┬────────────────────────────────┐\n";
echo "│ Patrimônio │ Data Aquis.  │ Descrição                      │\n";
echo "├────────────┼──────────────┼────────────────────────────────┤\n";
foreach ($amostra as $p) {
    $desc = substr($p->DEPATRIMONIO ?? '-', 0, 30);
    printf("│ %-10s │ %-12s │ %-30s │\n", $p->NUPATRIMONIO, $p->DTAQUISICAO ?? 'null', $desc);
}
echo "└────────────┴──────────────┴────────────────────────────────┘\n";

echo "\n✅ Análise concluída!\n";
echo "\n💡 CONCLUSÃO:\n";
if (count($divergencias) === 0) {
    echo "   ✅ Todas as datas verificadas estão CORRETAS no banco!\n";
} else {
    $numDiv = count($divergencias);
    echo "   ⚠️  Há {$numDiv} divergências que precisam de atenção.\n";
    echo "   💾 As datas do TXT podem não ter sido importadas corretamente.\n";
}
