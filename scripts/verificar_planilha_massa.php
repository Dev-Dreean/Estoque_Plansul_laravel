<?php
// one-off: Análise rápida da planilha de alteração em massa
// Criado: 2026-01-12

require 'vendor/autoload.php';

use Spatie\SimpleExcel\SimpleExcelReader;

$arquivo = 'Massa/Alterações em massa.xlsx';

if (!file_exists($arquivo)) {
    echo "❌ Arquivo não encontrado: {$arquivo}\n";
    exit(1);
}

echo "📋 [ANÁLISE PLANILHA] Alterações em massa\n";
echo "================================\n\n";

$patrimonios = [];
$linha = 0;

SimpleExcelReader::create($arquivo)->getRows()->each(function(array $row) use (&$patrimonios, &$linha) {
    $linha++;
    
    // Tentar encontrar número do patrimônio
    foreach ($row as $key => $value) {
        $keyLower = strtolower((string)$key);
        if (in_array($keyLower, ['nupatrimonio', 'patrimonio', 'numero'])) {
            if (is_numeric($value)) {
                $patrimonios[] = (int)$value;
                break;
            }
        }
    }
    
    // Se não encontrou por nome, pegar primeiro valor numérico
    if (count($patrimonios) < $linha) {
        foreach ($row as $value) {
            if (is_numeric($value)) {
                $patrimonios[] = (int)$value;
                break;
            }
        }
    }
});

echo "✅ Total de patrimônios: " . count($patrimonios) . "\n";
echo "✅ Primeiros 10: " . implode(', ', array_slice($patrimonios, 0, 10)) . "\n";
if (count($patrimonios) > 10) {
    echo "   ... e mais " . (count($patrimonios) - 10) . " patrimônios\n";
}
echo "\n";

echo "📋 Colunas da planilha:\n";
$reader = SimpleExcelReader::create($arquivo);
$firstRow = $reader->getRows()->first();
foreach ($firstRow as $key => $value) {
    echo "   • {$key}\n";
}
