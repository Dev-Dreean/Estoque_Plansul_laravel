<?php
/**
 * Gera SQL para SUBSTITUIR usuários (PRE) pelos usuários corretos
 * E redireciona todos os lançamentos para o usuário correto
 * 
 * Mapa de conversão:
 * ABIGAIL (PRE) → ABIGAIL
 * ANDRE (PRE) → ANDRE
 * BEA.SC (PRE) → BEA.SC
 * BEATRIZ.SC (PRE) → BEATRIZ.SC
 * CURY.SC (PRE) → CURY.SC
 * IANDRAF.SC (PRE) → IANDRAF.SC
 * etc.
 */

// Mapa de usuários: versão (PRE) → versão correta
$usuarioMap = [
    'ABIGAIL (PRE)' => 'ABIGAIL',
    'ANDRE (PRE)' => 'ANDRE',
    'ANDRE LUIS PAIM FURTADO (PRE)' => 'ANDRE LUIS PAIM FURTADO',
    'BEA.SC (PRE)' => 'BEA.SC',
    'BEATRIZ PATRICIA V... (PRE)' => 'BEATRIZ PATRICIA V...',
    'BEATRIZ.SC (PRE)' => 'BEATRIZ.SC',
    'BRUNO (PRE)' => 'BRUNO',
    'CURY.SC (PRE)' => 'CURY.SC',
    'GISELE DE SOUZA PE... (PRE)' => 'GISELE DE SOUZA PE...',
    'IANDRAF.SC (PRE)' => 'IANDRAF.SC',
];

$sqlFile = __DIR__ . '/../storage/output/update_patr_usuario_pre_to_correct.sql';
$handle = fopen($sqlFile, 'w');

fwrite($handle, "-- Script para SUBSTITUIR usuários (PRE) pelos usuários corretos em patr\n");
fwrite($handle, "-- Gerado em: " . date('Y-m-d H:i:s') . "\n");
fwrite($handle, "-- Redireciona ALL os lançamentos das versões (PRE) para os usuários corretos\n");
fwrite($handle, "-- Depois remove as versões (PRE)\n\n");

fwrite($handle, "USE plansul04;\n\n");

fwrite($handle, "START TRANSACTION;\n\n");

// Backup
fwrite($handle, "-- Backup antes de modificar\n");
fwrite($handle, "CREATE TABLE IF NOT EXISTS patr_backup_before_usuario_cleanup AS SELECT * FROM patr;\n");
fwrite($handle, "CREATE TABLE IF NOT EXISTS movimentacao_backup_before_usuario_cleanup AS SELECT * FROM movimentacao;\n\n");

// Etapa 1: Atualizar movimentações (histórico)
fwrite($handle, "-- ETAPA 1: Atualizar histórico de movimentações\n");
foreach ($usuarioMap as $preUser => $correctUser) {
    $preUserEsc = str_replace("'", "''", $preUser);
    $correctUserEsc = str_replace("'", "''", $correctUser);
    fwrite($handle, "UPDATE movimentacao SET USUARIO_RESPONSAVEL = '$correctUserEsc' WHERE USUARIO_RESPONSAVEL = '$preUserEsc';\n");
}
fwrite($handle, "\n");

// Etapa 2: Atualizar patrimônios
fwrite($handle, "-- ETAPA 2: Atualizar patrimônios - substitui (PRE) pelo correto\n");
foreach ($usuarioMap as $preUser => $correctUser) {
    $preUserEsc = str_replace("'", "''", $preUser);
    $correctUserEsc = str_replace("'", "''", $correctUser);
    fwrite($handle, "UPDATE patr SET USUARIO = '$correctUserEsc' WHERE USUARIO = '$preUserEsc';\n");
}
fwrite($handle, "\n");

fwrite($handle, "COMMIT;\n\n");

// Validação
fwrite($handle, "-- VALIDAÇÃO: Verificar se ainda existem (PRE)\n");
fwrite($handle, "SELECT COUNT(*) AS total_com_pre FROM patr WHERE USUARIO LIKE '%PRE%';\n");
fwrite($handle, "SELECT COUNT(*) AS total_com_pre_mov FROM movimentacao WHERE USUARIO_RESPONSAVEL LIKE '%PRE%';\n\n");

// Listagem final
fwrite($handle, "-- Distribuição final de usuários em patrimônios\n");
fwrite($handle, "SELECT USUARIO, COUNT(*) as total FROM patr WHERE USUARIO IS NOT NULL AND USUARIO != '' GROUP BY USUARIO ORDER BY total DESC;\n");

fclose($handle);

echo "✓ SQL de substituição gerado com sucesso!\n";
echo "Arquivo: $sqlFile\n\n";

echo "=== MAPA DE CONVERSÃO ===\n";
foreach ($usuarioMap as $preUser => $correctUser) {
    echo "  $preUser → $correctUser\n";
}

echo "\n=== PRÓXIMOS PASSOS ===\n";
echo "1. Abra o phpMyAdmin do KingHost\n";
echo "2. Aba SQL\n";
echo "3. Cole TODO o conteúdo de: storage/output/update_patr_usuario_pre_to_correct.sql\n";
echo "4. Execute\n";
echo "5. Após sucesso, execute: php artisan cache:clear\n";
