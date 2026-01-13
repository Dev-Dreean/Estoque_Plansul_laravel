<?php
// one-off: Análise completa e alteração inteligente em massa
// Criado: 2026-01-12

require 'vendor/autoload.php';

use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
require __DIR__ . '/bootstrap/app.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$arquivo = 'Massa/Alterações em massa.xlsx';

if (!file_exists($arquivo)) {
    echo "❌ Arquivo não encontrado: {$arquivo}\n";
    exit(1);
}

echo "🔍 [ANÁLISE INTELIGENTE] Alteração em massa de patrimônios\n";
echo "============================================================\n\n";

// 1. LER PATRIMÔNIOS DA PLANILHA
echo "📋 PASSO 1: Lendo planilha...\n";
$patrimoniosParaAlterar = [];

try {
    SimpleExcelReader::create($arquivo)->getRows()->each(function(array $row) use (&$patrimoniosParaAlterar) {
        foreach ($row as $key => $value) {
            $keyLower = strtolower((string)$key);
            if (in_array($keyLower, ['nupatrimonio', 'patrimonio', 'numero', 'n° patrimônio'])) {
                if (is_numeric($value)) {
                    $patrimoniosParaAlterar[] = (int)$value;
                    break;
                }
            }
        }
        
        // Se não encontrou por nome, pegar primeiro valor numérico
        if (empty($patrimoniosParaAlterar) || count($patrimoniosParaAlterar) < count($row)) {
            foreach ($row as $value) {
                if (is_numeric($value) && !in_array((int)$value, $patrimoniosParaAlterar)) {
                    $patrimoniosParaAlterar[] = (int)$value;
                    break;
                }
            }
        }
    });
} catch (Exception $e) {
    echo "❌ Erro ao ler planilha: " . $e->getMessage() . "\n";
    exit(1);
}

$patrimoniosParaAlterar = array_unique($patrimoniosParaAlterar);
sort($patrimoniosParaAlterar);

echo "✅ Total de patrimônios na planilha: " . count($patrimoniosParaAlterar) . "\n";
echo "✅ Patrimônios: " . implode(', ', array_slice($patrimoniosParaAlterar, 0, 20)) . (count($patrimoniosParaAlterar) > 20 ? '...' : '') . "\n\n";

if (empty($patrimoniosParaAlterar)) {
    echo "❌ Nenhum patrimônio encontrado na planilha\n";
    exit(1);
}

// 2. SALVAR LISTA PARA USO NO SSH
$listaPath = __DIR__ . '/storage/temp_patrimonios_lista.json';
file_put_contents($listaPath, json_encode($patrimoniosParaAlterar));
echo "💾 Lista salva em: {$listaPath}\n\n";

echo "📊 Patrimônios que serão alterados:\n";
echo json_encode($patrimoniosParaAlterar, JSON_PRETTY_PRINT) . "\n";
