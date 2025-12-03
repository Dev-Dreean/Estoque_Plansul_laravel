#!/usr/bin/env php
<?php

echo "\n=== TESTE DE ARQUIVO ===\n\n";

$file = __DIR__ . '/../storage/imports/Novo import/LocalProjeto.TXT';
echo "Arquivo: $file\n";
echo "Existe: " . (file_exists($file) ? 'SIM' : 'NÃO') . "\n\n";

$f = fopen($file, 'r');
if (!$f) {
    echo "ERRO: Não conseguiu abrir arquivo\n";
    exit(1);
}

// Pular cabeçalho
$h1 = fgets($f);
$h2 = fgets($f);

echo "Cabeçalho 1: " . str_replace("\n", "", $h1) . "\n";
echo "Cabeçalho 2: " . str_replace("\n", "", $h2) . "\n\n";

// Ler linha de dados
$line = fgets($f);
$line_clean = trim($line);

echo "Linha raw: " . str_replace(" ", "[ESQ]", $line_clean) . "\n";
echo "Comprimento: " . strlen($line_clean) . "\n\n";

// Testar separadores
echo "=== TENTANDO SEPARADORES ===\n";

$p1 = str_getcsv($line_clean, ';');
echo "Com ';': " . count($p1) . " partes\n";

$p2 = str_getcsv($line_clean, ',');
echo "Com ',': " . count($p2) . " partes\n";

$p3 = preg_split('/\s+/', $line_clean, -1, PREG_SPLIT_NO_EMPTY);
echo "Com espaços: " . count($p3) . " partes\n";
for ($i = 0; $i < min(5, count($p3)); $i++) {
    echo "  [$i]: '" . $p3[$i] . "'\n";
}

fclose($f);

echo "\n=== FIM DO TESTE ===\n\n";
