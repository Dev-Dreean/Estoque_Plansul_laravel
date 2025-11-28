<?php
/**
 * Analisa o dump SQL para ver distribuição de USUARIO
 */

$dumpFile = __DIR__ . '/../storage/app/patrimonios_dump.sql';

if (!file_exists($dumpFile)) {
    die("Erro: arquivo de dump não encontrado\n");
}

echo "[*] Lendo dump SQL...\n";
$content = file_get_contents($dumpFile);
$lines = explode("\n", $content);

$totalWithUser = 0;
$totalSystem = 0;
$totalNull = 0;
$usuarios = [];

foreach ($lines as $line) {
    if (strpos($line, 'INSERT INTO patr VALUES') === 0) {
        // Parse simplificado
        if (preg_match('/VALUES \((.*)\);/', $line, $m)) {
            $partes = explode("','", $m[1]);
            if (count($partes) >= 21) {
                // Campo USUARIO está no índice 20
                $user = trim($partes[20], " '\"");
                
                if (!$user || $user === '') {
                    $totalNull++;
                } elseif ($user === 'SISTEMA') {
                    $totalSystem++;
                } else {
                    $totalWithUser++;
                    if (!isset($usuarios[$user])) {
                        $usuarios[$user] = 0;
                    }
                    $usuarios[$user]++;
                }
            }
        }
    }
}

echo "\n=== ANÁLISE DO DUMP SQL ===\n\n";
echo "Total com usuário real: $totalWithUser\n";
echo "Total com 'SISTEMA': $totalSystem\n";
echo "Total com NULL/vazio: $totalNull\n";
echo "Total geral: " . ($totalWithUser + $totalSystem + $totalNull) . "\n";

echo "\n=== USUÁRIOS REAIS ENCONTRADOS ===\n";
arsort($usuarios);
foreach ($usuarios as $user => $count) {
    echo "$user: $count patrimônios\n";
}
