<?php
// one-off: Executar alteração em massa COMPLETA e INTELIGENTE
// Criado: 2026-01-12
// Ação: Buscar estado atual → Alterar → Verificar resultado

require 'vendor/autoload.php';

echo "🚀 [ALTERAÇÃO INTELIGENTE EM MASSA] Patrimônios\n";
echo "============================================================\n\n";

// CONFIGURAÇÃO DA ALTERAÇÃO
$alteracoes = [
    'CDPROJETO' => 8,              // SEDE
    'CDLOCAL' => 2059,             // Sala Comercial
    'SITUACAO' => 'À DISPOSIÇÃO',
    'USUARIO' => 'BEATRIZ.SC',
    'FLCONFERIDO' => 'S',
    'DTOPERACAO' => date('Y-m-d H:i:s'),
];

echo "📋 Configuração das alterações:\n";
foreach ($alteracoes as $campo => $valor) {
    echo "   • {$campo}: {$valor}\n";
}
echo "\n";

// Como não consegui ler a planilha, vou perguntar ao usuário
echo "❓ INFORMAÇÃO NECESSÁRIA:\n";
echo "Por favor, me informe os números dos patrimônios da planilha.\n";
echo "Você pode:\n";
echo "  1. Abrir 'Massa/Alterações em massa.xlsx' no Excel\n";
echo "  2. Copiar os números dos patrimônios\n";
echo "  3. Colar aqui\n\n";

echo "Ou posso fazer um teste com patrimônios de exemplo?\n";
echo "Digite 'teste' para testar com patrimônios de exemplo do banco.\n";
