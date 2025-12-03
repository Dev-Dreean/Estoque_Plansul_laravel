#!/usr/bin/env php
<?php
$env = [];
foreach (file(__DIR__ . '/../.env') as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') continue;
    @list($k, $v) = explode('=', $line, 2);
    if ($k && $v) $env[trim($k)] = trim($v, '"\'');
}

$pdo = new PDO(
    sprintf("mysql:host=%s;dbname=%s", $env['DB_HOST'], $env['DB_DATABASE']),
    $env['DB_USERNAME'], $env['DB_PASSWORD']
);

echo "\n=== VERIFICAÇÃO DE DADOS ===\n\n";

// Verificar patrimônios que deveriam ter sido atualizados
$patrimonios = [3, 38, 45, 100];

foreach ($patrimonios as $num) {
    $r = $pdo->query("SELECT NUPATRIMONIO, SITUACAO, USUARIO, CDPROJETO FROM patr WHERE NUPATRIMONIO = $num")->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        echo "Patrimônio #$num:\n";
        echo "  SITUACAO: {$r['SITUACAO']}\n";
        echo "  USUARIO: {$r['USUARIO']}\n";
        echo "  CDPROJETO: {$r['CDPROJETO']}\n";
    } else {
        echo "Patrimônio #$num: NÃO ENCONTRADO\n";
    }
    echo "\n";
}

// Totais
echo "=== TOTAIS ===\n";
echo "Patrimônios: " . $pdo->query("SELECT COUNT(*) FROM patr")->fetchColumn() . "\n";
echo "Locais: " . $pdo->query("SELECT COUNT(*) FROM locais_projeto")->fetchColumn() . "\n";
echo "Histórico: " . $pdo->query("SELECT COUNT(*) FROM movpartr")->fetchColumn() . "\n";
echo "Usuários vinculados: " . $pdo->query("SELECT COUNT(*) FROM patr WHERE USUARIO IS NOT NULL")->fetchColumn() . "\n";
echo "\n";
