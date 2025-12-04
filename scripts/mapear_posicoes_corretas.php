<?php
/**
 * Mapear TODAS as posições corretas no arquivo
 */

$arquivo = __DIR__ . '/../Patrimonio_NOVO.TXT';
$linhas = file($arquivo, FILE_IGNORE_NEW_LINES);

foreach ($linhas as $idx => $linha) {
    if (preg_match('/^5243\s/', $linha)) {
        echo "=== PATRIMONIO 5243 - MAPEAMENTO COMPLETO ===\n";
        echo "Linha: " . ($idx + 1) . "\n";
        echo "Tamanho: " . strlen($linha) . "\n\n";
        
        // Procurar cada campo conhecido
        $campos = [
            'SITUACAO' => 'Á DISPOSIÇÃO',
            'CDLOCAL' => '2059',
            'DEPATRIMONIO' => 'SALA COMERCIAL',
            'CDMATRFUNCIONARIO' => '133838',
            'CDPROJETO' => '8',
            'USUARIO' => 'BEA.SC',
            'DTOPERACAO' => '01/12/2025',
            'DTAQUISICAO' => '11/12/2011'
        ];
        
        foreach ($campos as $nome => $valor) {
            $pos = strpos($linha, $valor);
            if ($pos !== false) {
                $contexto_antes = substr($linha, max(0, $pos-15), 15);
                $contexto_depois = substr($linha, $pos + strlen($valor), 15);
                echo sprintf("%-20s @ pos %3d: [%s|%s|%s]\n", 
                    $nome, $pos, $contexto_antes, $valor, $contexto_depois);
            } else {
                echo sprintf("%-20s: NÃO ENCONTRADO\n", $nome);
            }
        }
        
        echo "\n=== PROPOSTA DE EXTRAÇÃO ===\n";
        echo "NUPATRIMONIO: [" . trim(substr($linha, 0, 16)) . "]\n";
        echo "SITUACAO (16-?): [" . trim(substr($linha, 16, 35)) . "]\n";
        
        // Com base nas posições encontradas:
        $pos_func = strpos($linha, '133838');
        $pos_usuario = strpos($linha, 'BEA.SC');
        $pos_depatr = strpos($linha, 'SALA COMERCIAL');
        
        if ($pos_depatr !== false) {
            echo "DEPATRIMONIO ($pos_depatr-?): [" . substr($linha, $pos_depatr, 90) . "]\n";
        }
        if ($pos_func !== false) {
            echo "CDMATRFUNCIONARIO ($pos_func-?): [" . substr($linha, $pos_func, 20) . "]\n";
        }
        if ($pos_usuario !== false) {
            echo "USUARIO ($pos_usuario-?): [" . substr($linha, $pos_usuario, 15) . "]\n";
        }
        
        break;
    }
}
