<?php

// Debug detalhado do loop de importação

$arquivo = '/home/plansul/www/estoque-laravel/patrimonio.TXT';

if (!file_exists($arquivo)) {
    die("❌ Arquivo não encontrado\n");
}

echo "🔍 DEBUG DETALHADO DO LOOP\n\n";

$lines = file($arquivo, FILE_IGNORE_NEW_LINES);
echo "Total linhas: " . count($lines) . "\n\n";

// Simular o loop exatamente como no importador
$processados = 0;
$pulados = 0;
$nao_numericos = 0;

for ($i = 2; $i < min(100, count($lines)); $i++) {
    $line = $lines[$i];
    
    // Log detalhado das primeiras 10 linhas
    if ($i < 12) {
        echo "─────────────────────────────────────────────────────\n";
        echo "Linha $i:\n";
        echo "├─ Tamanho: " . strlen(trim($line)) . " chars\n";
        echo "├─ Primeiros 100: " . substr($line, 0, 100) . "\n";
    }
    
    // Pular linhas vazias, cabeçalhos ou separadores
    if (strlen(trim($line)) < 10 || strpos($line, '===') !== false) {
        if ($i < 12) echo "└─ ⏭️  PULADO: Vazio ou separador\n\n";
        $pulados++;
        continue;
    }
    
    // Converter encoding se necessário
    if (!mb_check_encoding($line, 'UTF-8')) {
        $line = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $line);
    }
    
    // Extrair NUPATRIMONIO
    $nupatrimonio = trim(substr($line, 0, 16));
    
    if ($i < 12) {
        echo "├─ NUPATRIMONIO (0-16): '$nupatrimonio'\n";
        echo "├─ is_numeric: " . (is_numeric($nupatrimonio) ? 'SIM' : 'NÃO') . "\n";
    }
    
    // Validar se é número
    if (!is_numeric($nupatrimonio)) {
        if ($i < 12) echo "└─ ⏭️  PULADO: Não é numérico\n\n";
        $nao_numericos++;
        continue;
    }
    
    // Este seria processado
    if ($i < 12) {
        $situacao = trim(substr($line, 16, 35));
        $marca = trim(substr($line, 51, 35));
        $usuario = trim(substr($line, 494, 15));
        
        echo "├─ SITUACAO (16-51): '$situacao'\n";
        echo "├─ MARCA (51-86): '$marca'\n";
        echo "├─ USUARIO (494-509): '$usuario'\n";
        echo "└─ ✅ SERIA PROCESSADO\n\n";
    }
    
    $processados++;
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "RESUMO DO LOOP (primeiras 100 linhas):\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
echo "Pulados (vazios/separadores): $pulados\n";
echo "Pulados (não numéricos): $nao_numericos\n";
echo "Processados: $processados\n";
echo "\nTotal analisado: " . ($pulados + $nao_numericos + $processados) . "\n";

// Análise completa
echo "\n\n═══════════════════════════════════════════════════════════════\n";
echo "ANÁLISE COMPLETA DO ARQUIVO:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$total_pulados = 0;
$total_nao_numericos = 0;
$total_processados = 0;

for ($i = 2; $i < count($lines); $i++) {
    $line = $lines[$i];
    
    if (strlen(trim($line)) < 10 || strpos($line, '===') !== false) {
        $total_pulados++;
        continue;
    }
    
    if (!mb_check_encoding($line, 'UTF-8')) {
        $line = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $line);
    }
    
    $nupatrimonio = trim(substr($line, 0, 16));
    
    if (!is_numeric($nupatrimonio)) {
        $total_nao_numericos++;
        continue;
    }
    
    $total_processados++;
}

echo "Linhas totais: " . count($lines) . "\n";
echo "Início do loop (linha 2)\n";
echo "Fim do loop (linha " . (count($lines) - 1) . ")\n\n";
echo "Pulados (vazios/separadores): $total_pulados\n";
echo "Pulados (não numéricos): $total_nao_numericos\n";
echo "QUE SERIAM PROCESSADOS: $total_processados\n";

echo "\n✅ Debug concluído!\n";
