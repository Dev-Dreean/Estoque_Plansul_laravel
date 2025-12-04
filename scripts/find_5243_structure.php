<?php
/**
 * Encontrar estrutura exata do patrimônio 5243
 */

$arquivo = __DIR__ . '/../Patrimonio_NOVO.TXT';
$linhas = file($arquivo, FILE_IGNORE_NEW_LINES);

foreach ($linhas as $idx => $linha) {
    if (preg_match('/^5243\s/', $linha)) {
        echo "=== PATRIMONIO 5243 ENCONTRADO ===\n";
        echo "Linha: " . ($idx + 1) . "\n";
        echo "Tamanho: " . strlen($linha) . "\n\n";
        
        echo "ESTRUTURA COMPLETA:\n";
        echo "Pos [0-15]: [" . substr($linha, 0, 16) . "]\n";
        echo "Pos [16-50]: [" . substr($linha, 16, 35) . "]\n";
        echo "Pos [250-280]: [" . substr($linha, 250, 31) . "]\n";
        echo "Pos [290-320]: [" . substr($linha, 290, 31) . "]\n";
        echo "Pos [305-319]: [" . substr($linha, 305, 15) . "]\n";
        
        echo "\nPROCURANDO 'BEA':\n";
        $pos_bea = strpos($linha, 'BEA');
        if ($pos_bea !== false) {
            echo "BEA encontrado na posição: $pos_bea\n";
            echo "Contexto: [" . substr($linha, max(0, $pos_bea-10), 30) . "]\n";
        } else {
            echo "BEA NÃO ENCONTRADO!\n";
        }
        
        echo "\nPROCURANDO '133838':\n";
        $pos_func = strpos($linha, '133838');
        if ($pos_func !== false) {
            echo "133838 encontrado na posição: $pos_func\n";
            echo "Contexto: [" . substr($linha, max(0, $pos_func-10), 40) . "]\n";
        }
        
        break;
    }
}
