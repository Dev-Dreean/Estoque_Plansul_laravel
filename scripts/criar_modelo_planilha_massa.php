<?php
// one-off: Criar planilha modelo para alteração em massa
// Criado: 2026-01-12

require 'vendor/autoload.php';

use Spatie\SimpleExcel\SimpleExcelWriter;

$arquivo = 'Massa/modelo_alteracao_massa.xlsx';

echo "📋 Criando planilha modelo...\n";

$writer = SimpleExcelWriter::create($arquivo);

// Adicionar cabeçalho com instruções
$writer->addRow([
    'NUPATRIMONIO' => 'NUPATRIMONIO',
    'CDPROJETO' => 'CDPROJETO', 
    'CDLOCAL' => 'CDLOCAL',
    'SITUACAO' => 'SITUACAO',
    'USUARIO' => 'USUARIO',
    'FLCONFERIDO' => 'FLCONFERIDO'
]);

// Adicionar exemplos
$writer->addRow([
    'NUPATRIMONIO' => '(cole aqui os números)',
    'CDPROJETO' => '8',
    'CDLOCAL' => '2059',
    'SITUACAO' => 'À DISPOSIÇÃO',
    'USUARIO' => 'BEATRIZ.SC',
    'FLCONFERIDO' => 'S'
]);

echo "✅ Planilha modelo criada: {$arquivo}\n\n";
echo "📋 Instruções:\n";
echo "   1. Abra o arquivo em Excel\n";
echo "   2. Delete a linha de exemplo\n";
echo "   3. Cole os números dos patrimônios na coluna NUPATRIMONIO\n";
echo "   4. As outras colunas já estão preenchidas com os valores corretos\n";
echo "   5. Salve e execute o comando de alteração\n";
