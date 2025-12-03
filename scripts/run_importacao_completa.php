<?php
/**
 * EXECUTOR MASTER DE IMPORTAÇÃO COMPLETA
 * 
 * Este script executa TODAS as importações na ordem correta:
 * 1. Validação pré-importação
 * 2. Backup do banco (obrigatório)
 * 3. Importação de Locais
 * 4. Importação de Patrimônios (com atualização)
 * 5. Importação de Histórico
 * 
 * USO:
 * php scripts/run_importacao_completa.php
 * 
 * FLAGS OPCIONAIS:
 * --skip-backup : Pula backup (NÃO RECOMENDADO)
 * --skip-validation : Pula validação prévia
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         IMPORTAÇÃO COMPLETA - EXECUTOR MASTER              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "Data: " . date('d/m/Y H:i:s') . "\n\n";

$scriptsDir = __DIR__;
$skipBackup = in_array('--skip-backup', $argv);
$skipValidation = in_array('--skip-validation', $argv);

$etapas = [
    [
        'nome' => 'VALIDAÇÃO PRÉ-IMPORTAÇÃO',
        'script' => 'validate_pre_import.php',
        'obrigatoria' => !$skipValidation,
        'descricao' => 'Verifica arquivos, usuários, projetos e funcionários'
    ],
    [
        'nome' => 'BACKUP DO BANCO DE DADOS',
        'script' => 'backup_database.php',
        'obrigatoria' => !$skipBackup,
        'descricao' => 'Cria backup completo antes de qualquer alteração'
    ],
    [
        'nome' => 'IMPORTAÇÃO DE LOCAIS',
        'script' => 'import_localprojeto.php',
        'obrigatoria' => true,
        'descricao' => 'Importa/atualiza locais de projeto'
    ],
    [
        'nome' => 'IMPORTAÇÃO DE PATRIMÔNIOS',
        'script' => 'import_patrimonio_completo_v2.php',
        'obrigatoria' => true,
        'descricao' => 'Importa novos e atualiza patrimônios existentes'
    ],
    [
        'nome' => 'IMPORTAÇÃO DE HISTÓRICO',
        'script' => 'import_historico_movimentacao.php',
        'obrigatoria' => true,
        'descricao' => 'Importa histórico de movimentações'
    ]
];

$totalEtapas = count(array_filter($etapas, fn($e) => $e['obrigatoria']));
$etapaAtual = 0;

echo "📋 ETAPAS A EXECUTAR: $totalEtapas\n\n";

foreach ($etapas as $etapa) {
    if (!$etapa['obrigatoria']) {
        echo "⏭️  Pulando: {$etapa['nome']} (--skip)\n\n";
        continue;
    }
    
    $etapaAtual++;
    
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ETAPA $etapaAtual/$totalEtapas: {$etapa['nome']}" . str_repeat(' ', 59 - strlen($etapa['nome']) - strlen("$etapaAtual/$totalEtapas")) . "║\n";
    echo "╠════════════════════════════════════════════════════════════╣\n";
    echo "║  {$etapa['descricao']}" . str_repeat(' ', 57 - strlen($etapa['descricao'])) . "║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    $scriptPath = "$scriptsDir/{$etapa['script']}";
    
    if (!file_exists($scriptPath)) {
        echo "❌ ERRO: Script não encontrado: {$etapa['script']}\n";
        echo "   Caminho esperado: $scriptPath\n\n";
        exit(1);
    }
    
    $inicio = microtime(true);
    
    // Executar script
    $comando = "php \"$scriptPath\"";
    $output = [];
    $returnCode = 0;
    
    exec($comando . " 2>&1", $output, $returnCode);
    
    $duracao = round(microtime(true) - $inicio, 2);
    
    // Mostrar output
    foreach ($output as $linha) {
        echo $linha . "\n";
    }
    
    echo "\n⏱️  Tempo de execução: {$duracao}s\n\n";
    
    if ($returnCode !== 0) {
        echo "❌ ERRO CRÍTICO na etapa: {$etapa['nome']}\n";
        echo "   Código de retorno: $returnCode\n";
        echo "   Importação ABORTADA.\n\n";
        
        if ($etapaAtual > 2) {
            echo "⚠️  IMPORTANTE: Algumas etapas foram concluídas.\n";
            echo "   Se necessário, restaure o backup antes de tentar novamente.\n";
            echo "   Backup: storage/backups/\n\n";
        }
        
        exit(1);
    }
    
    echo "✅ Etapa concluída com sucesso!\n\n";
    echo str_repeat("─", 64) . "\n\n";
}

// ========================================================================
// RESUMO FINAL
// ========================================================================
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           IMPORTAÇÃO COMPLETA FINALIZADA!                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "✅ TODAS AS ETAPAS CONCLUÍDAS COM SUCESSO!\n\n";

echo "📊 PRÓXIMOS PASSOS:\n";
echo "  1. Verificar logs em storage/logs/laravel.log\n";
echo "  2. Acessar o sistema e validar:\n";
echo "     • Patrimônios atualizados corretamente\n";
echo "     • Usuários vinculados preservados\n";
echo "     • Locais importados\n";
echo "     • Histórico registrado\n\n";

echo "  3. Se tudo estiver OK:\n";
echo "     • Commitar mudanças (se houver)\n";
echo "     • Fazer push para o repositório\n";
echo "     • Replicar no servidor KingHost\n\n";

echo "  4. Se houver problemas:\n";
echo "     • Restaurar backup: php scripts/restore_backup.php\n";
echo "     • Revisar logs e corrigir\n";
echo "     • Executar novamente\n\n";

echo "📁 Backups disponíveis em: storage/backups/\n";
echo "📝 Logs disponíveis em: storage/logs/\n\n";

$timestamp = date('d/m/Y H:i:s');
echo "✅ Processo concluído em: $timestamp\n";
