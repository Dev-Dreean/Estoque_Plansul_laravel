#!/usr/bin/env php
<?php

/**
 * Script de Importação Simplificado para KingHost
 * Não usa artisan - conexão direta com banco de dados
 */

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  IMPORTAÇÃO PLANSUL - VERSÃO KINGHOST SIMPLIFICADA         ║\n";
echo "║  Data: " . date('d/m/Y H:i:s') . "                                      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Carregar .env
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    echo "❌ Erro: Arquivo .env não encontrado em $envFile\n";
    exit(1);
}

// Parse .env manualmente
$env = [];
foreach (file($envFile) as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, '#') === 0) continue;
    list($key, $val) = explode('=', $line, 2) + [null, null];
    if ($key && $val) {
        $env[trim($key)] = trim($val, '"\'');
    }
}

// Configurar banco de dados
$dbHost = $env['DB_HOST'] ?? 'localhost';
$dbPort = $env['DB_PORT'] ?? 3306;
$dbName = $env['DB_DATABASE'] ?? '';
$dbUser = $env['DB_USERNAME'] ?? '';
$dbPass = $env['DB_PASSWORD'] ?? '';

echo "📦 Conectando ao banco de dados...\n";
echo "   Host: $dbHost:$dbPort\n";
echo "   Banco: $dbName\n\n";

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "✅ Conexão com banco de dados OK!\n\n";
} catch (Exception $e) {
    echo "❌ Erro ao conectar: " . $e->getMessage() . "\n";
    exit(1);
}

// Verificar arquivos de importação
$importDir = __DIR__ . '/../storage/imports/Novo import';
$files = [
    'Patrimonio.txt' => 'Patrimônios',
    'LocalProjeto.TXT' => 'Locais',
    'Hist_movpatr.TXT' => 'Histórico'
];

echo "🔍 VALIDAÇÃO DE ARQUIVOS\n";
echo "════════════════════════════════════════════════════════════\n";

$filesToImport = [];
foreach ($files as $filename => $label) {
    $path = "$importDir/$filename";
    if (file_exists($path)) {
        $size = filesize($path);
        $lines = count(file($path));
        echo "✓ $label: $filename ($lines linhas, " . round($size/1024, 1) . " KB)\n";
        $filesToImport[$filename] = $label;
    } else {
        echo "⚠ $label: $filename (NÃO ENCONTRADO)\n";
    }
}

echo "\n";

// Contar registros antes
echo "📊 CONTAGEM ANTES DA IMPORTAÇÃO\n";
echo "════════════════════════════════════════════════════════════\n";

$patriBefore = $pdo->query("SELECT COUNT(*) as total FROM patr")->fetchColumn();
$localBefore = $pdo->query("SELECT COUNT(*) as total FROM locais_projeto")->fetchColumn();
$histBefore = $pdo->query("SELECT COUNT(*) as total FROM movpartr")->fetchColumn();

echo "Patrimônios:  $patriBefore\n";
echo "Locais:       $localBefore\n";
echo "Histórico:    $histBefore\n";

echo "\n";
echo "✅ VALIDAÇÃO CONCLUÍDA COM SUCESSO!\n";
echo "   Sistema pronto para importação.\n\n";

echo "📋 PRÓXIMAS ETAPAS (execute no SSH):\n";
echo "════════════════════════════════════════════════════════════\n";
echo "cd /home/plansul/www/estoque-laravel\n";
echo "/usr/local/php/8.1/bin/php scripts/run_importacao_kinghost.php\n\n";

exit(0);
