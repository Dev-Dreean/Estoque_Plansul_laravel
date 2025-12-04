<?php
$arquivo = __DIR__ . '/../Patrimonio_NOVO.TXT';
$conteudo = file_get_contents($arquivo);

if (!mb_check_encoding($conteudo, 'UTF-8')) {
    $conteudo = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $conteudo);
}

$linhas = explode("\n", $conteudo);

// Procurar patrimonio 5243
$encontrado = false;
for ($i = 0; $i < count($linhas); $i++) {
    if (preg_match('/^5243\s/', $linhas[$i])) {
        echo "=== PATRIMONIO 5243 (linha $i) ===\n\n";
        
        // Mostrar 6 linhas (o bloco inteiro)
        for ($j = 0; $j < 6; $j++) {
            $idx = $i + $j;
            if ($idx < count($linhas)) {
                echo "LINHA " . ($j+1) . " (tamanho: " . strlen($linhas[$idx]) . "):\n";
                echo "'" . $linhas[$idx] . "'\n\n";
                
                // Mostrar com posições
                if ($j == 0) {
                    echo "  [0-15] NUPATR: '" . substr($linhas[$idx], 0, 16) . "'\n";
                    echo "  [16-50] SITUACAO: '" . substr($linhas[$idx], 16, 35) . "'\n";
                    echo "  [51-85] MARCA: '" . substr($linhas[$idx], 51, 35) . "'\n";
                    echo "  [86-96] CDLOCAL: '" . substr($linhas[$idx], 86, 11) . "'\n";
                    echo "  [97-131] MODELO: '" . substr($linhas[$idx], 97, 35) . "'\n\n";
                }
                
                if ($j == 1) {
                    echo "  [0-19] COR: '" . substr($linhas[$idx], 0, 20) . "'\n";
                    echo "  [20-34] DTAQ: '" . substr($linhas[$idx], 20, 15) . "'\n";
                    echo "  [35+] DEPATR: '" . substr($linhas[$idx], 35, 100) . "'\n\n";
                }
                
                if ($j == 4) {
                    echo "  [0-19] CDFUNC: '" . substr($linhas[$idx], 0, 20) . "'\n";
                    echo "  [20-32] CDPROJETO: '" . substr($linhas[$idx], 20, 13) . "'\n";
                    echo "  [33-47] NUDOCFISCAL: '" . substr($linhas[$idx], 33, 15) . "'\n";
                    echo "  [48-62] USUARIO: '" . substr($linhas[$idx], 48, 15) . "'\n";
                    echo "  [63-77] DTOPER: '" . substr($linhas[$idx], 63, 15) . "'\n";
                    echo "  [78-87] NUMOF: '" . substr($linhas[$idx], 78, 10) . "'\n";
                    echo "  [88-100] CODOBJETO: '" . substr($linhas[$idx], 88, 13) . "'\n\n";
                }
            }
        }
        
        $encontrado = true;
        break;
    }
}

if (!$encontrado) {
    echo "Patrimônio 5243 não encontrado\n";
}
