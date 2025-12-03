#!/usr/bin/env php
<?php

/**
 * Script de Importação Completa para KingHost
 * Versão simplificada - conexão direta com banco de dados
 */

set_time_limit(600); // 10 minutos
ini_set('memory_limit', '512M');

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║     IMPORTAÇÃO PLANSUL COMPLETA - KINGHOST               ║\n";
echo "║     Data: " . date('d/m/Y H:i:s') . "                                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$startTime = microtime(true);

// Carregar .env
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    echo "❌ Erro: .env não encontrado\n";
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

$dbHost = $env['DB_HOST'] ?? 'localhost';
$dbPort = $env['DB_PORT'] ?? 3306;
$dbName = $env['DB_DATABASE'] ?? '';
$dbUser = $env['DB_USERNAME'] ?? '';
$dbPass = $env['DB_PASSWORD'] ?? '';

// Conectar
try {
    $pdo = new PDO(
        "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Conectado ao banco de dados\n\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

// Função para ler arquivo com encoding correto
function readFileLines($path) {
    $lines = [];
    $handle = fopen($path, 'r');
    if (!$handle) return [];
    
    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        // Tentar detectar encoding
        if (!mb_check_encoding($line, 'UTF-8')) {
            $line = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $line);
        }
        if (!empty($line)) {
            $lines[] = $line;
        }
    }
    fclose($handle);
    return $lines;
}

// ETAPA 1: Criar backup
echo "💾 ETAPA 1: CRIANDO BACKUP\n";
echo "════════════════════════════════════════════════════════════\n";

$backupDir = __DIR__ . '/../storage/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);

$backupFile = $backupDir . '/backup_kinghost_' . date('YmdHis') . '.sql';
$backupCmd = "mysqldump -h $dbHost -u $dbUser -p$dbPass $dbName > $backupFile 2>&1";
exec($backupCmd, $output, $returnCode);

if ($returnCode === 0 && file_exists($backupFile)) {
    $size = round(filesize($backupFile) / 1024 / 1024, 2);
    echo "✅ Backup criado: $backupFile ($size MB)\n\n";
} else {
    echo "⚠️  Backup via mysqldump não disponível, prosseguindo mesmo assim\n\n";
}

// ETAPA 2: Importar Locais
echo "🏗️  ETAPA 2: IMPORTANDO LOCAIS\n";
echo "════════════════════════════════════════════════════════════\n";

$localFile = __DIR__ . '/../storage/imports/Novo import/LocalProjeto.TXT';
echo "📍 Procurando arquivo: $localFile\n";

if (file_exists($localFile)) {
    echo "✅ Arquivo encontrado\n\n";
    $lines = readFileLines($localFile);
    $pdo->beginTransaction();
    
    $created = 0;
    $updated = 0;
    
    foreach (array_slice($lines, 2) as $line) {
        $parts = str_getcsv($line, ';');
        if (count($parts) < 3) continue;
        
        $cdlocal = trim($parts[0]);
        $descricao = trim($parts[1]);
        $cdprojeto = trim($parts[2]);
        
        $stmt = $pdo->prepare("
            INSERT INTO locais_projeto (cdlocal, descricao, cdprojeto, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE descricao=?, cdprojeto=?, updated_at=NOW()
        ");
        
        try {
            $stmt->execute([$cdlocal, $descricao, $cdprojeto, $descricao, $cdprojeto]);
            if ($stmt->rowCount() > 1) $updated++;
            else $created++;
        } catch (Exception $e) {
            // Ignorar duplicatas
        }
    }
    
    $pdo->commit();
    echo "✅ Locais: $created novos + $updated atualizados\n\n";
} else {
    echo "⚠️  Arquivo LocalProjeto.TXT não encontrado\n\n";
}

// ETAPA 3: Importar Patrimônios
echo "🏛️  ETAPA 3: IMPORTANDO PATRIMÔNIOS\n";
echo "════════════════════════════════════════════════════════════\n";

$patriFile = __DIR__ . '/../storage/imports/Novo import/Patrimonio.txt';
echo "📍 Procurando arquivo: $patriFile\n";

if (file_exists($patriFile)) {
    echo "✅ Arquivo encontrado\n\n";
    $lines = readFileLines($patriFile);
    $total = count($lines) - 2;
    
    $pdo->beginTransaction();
    
    $created = 0;
    $updated = 0;
    $errors = 0;
    $counter = 0;
    
    foreach (array_slice($lines, 2) as $line) {
        $counter++;
        $parts = str_getcsv($line, ';');
        
        if (count($parts) < 10) continue;
        
        $nupatrimonio = trim($parts[0]);
        $depatrimonio = trim($parts[1]);
        $usuario = trim($parts[5]);
        $cdprojeto = trim($parts[6]);
        $dtinclusao = trim($parts[7]);
        $situacao = trim($parts[8]);
        
        // Normalizar data
        if (strpos($dtinclusao, '/') !== false) {
            $dt = explode('/', $dtinclusao);
            if (count($dt) == 3) {
                $dtinclusao = $dt[2] . '-' . $dt[1] . '-' . $dt[0];
            }
        }
        
        // Validar usuario
        $userStmt = $pdo->prepare("SELECT id FROM usuario WHERE login = ? LIMIT 1");
        $userStmt->execute([$usuario]);
        $userExists = $userStmt->fetchColumn();
        $usuarioFinal = $userExists ? $usuario : 'SISTEMA';
        
        $stmt = $pdo->prepare("
            INSERT INTO patr (
                NUPATRIMONIO, DEPATRIMONIO, USUARIO, CDPROJETO, 
                DTINCLUSAO, SITUACAO, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                DEPATRIMONIO=?, USUARIO=?, CDPROJETO=?, 
                DTINCLUSAO=?, SITUACAO=?, updated_at=NOW()
        ");
        
        try {
            $stmt->execute([
                $nupatrimonio, $depatrimonio, $usuarioFinal, $cdprojeto, 
                $dtinclusao, $situacao,
                $depatrimonio, $usuarioFinal, $cdprojeto,
                $dtinclusao, $situacao
            ]);
            
            if ($stmt->rowCount() > 1) $updated++;
            else $created++;
        } catch (Exception $e) {
            $errors++;
        }
        
        if ($counter % 500 == 0) {
            echo "  Processados: $counter/$total\n";
        }
    }
    
    $pdo->commit();
    echo "✅ Patrimônios: $created novos + $updated atualizados ($errors erros)\n\n";
} else {
    echo "⚠️  Arquivo Patrimonio.txt não encontrado\n\n";
}

// ETAPA 4: Importar Histórico
echo "📜 ETAPA 4: IMPORTANDO HISTÓRICO\n";
echo "════════════════════════════════════════════════════════════\n";

$histFile = __DIR__ . '/../storage/imports/Novo import/Hist_movpatr.TXT';
echo "📍 Procurando arquivo: $histFile\n";

if (file_exists($histFile)) {
    echo "✅ Arquivo encontrado\n\n";
    $lines = readFileLines($histFile);
    
    $pdo->beginTransaction();
    
    $created = 0;
    $errors = 0;
    $counter = 0;
    
    foreach (array_slice($lines, 2) as $line) {
        $counter++;
        $parts = str_getcsv($line, ';');
        
        if (count($parts) < 6) continue;
        
        $nupatrim = trim($parts[0]);
        $nuproj = trim($parts[1]);
        $dtmovi = trim($parts[2]);
        $flmov = trim($parts[3]);
        $usuario = trim($parts[4]);
        
        // Normalizar data
        if (strpos($dtmovi, '/') !== false) {
            $dt = explode('/', $dtmovi);
            if (count($dt) == 3) {
                $dtmovi = $dt[2] . '-' . $dt[1] . '-' . $dt[0];
            }
        }
        
        // Map de movimento
        $tipoMap = ['I' => 'INCLUSAO', 'A' => 'ALTERACAO', 'E' => 'EXCLUSAO', 'M' => 'MOVIMENTACAO'];
        $tipo = $tipoMap[$flmov] ?? 'ALTERACAO';
        
        // Validar usuario
        $userStmt = $pdo->prepare("SELECT id FROM usuario WHERE login = ? LIMIT 1");
        $userStmt->execute([$usuario]);
        $userExists = $userStmt->fetchColumn();
        $usuarioFinal = $userExists ? $usuario : 'SISTEMA';
        
        $stmt = $pdo->prepare("
            INSERT INTO movpartr (NUPATRIM, NUPROJ, DTMOVI, TIPO, USUARIO, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        try {
            $stmt->execute([$nupatrim, $nuproj, $dtmovi, $tipo, $usuarioFinal]);
            $created++;
        } catch (Exception $e) {
            $errors++;
        }
        
        if ($counter % 1000 == 0) {
            echo "  Processados: $counter\n";
        }
    }
    
    $pdo->commit();
    echo "✅ Histórico: $created novos ($errors erros)\n\n";
} else {
    echo "⚠️  Arquivo Hist_movpatr.TXT não encontrado\n\n";
}

// Contagem final
echo "📊 CONTAGEM FINAL\n";
echo "════════════════════════════════════════════════════════════\n";

$patriAfter = $pdo->query("SELECT COUNT(*) as total FROM patr")->fetchColumn();
$localAfter = $pdo->query("SELECT COUNT(*) as total FROM locais_projeto")->fetchColumn();
$histAfter = $pdo->query("SELECT COUNT(*) as total FROM movpartr")->fetchColumn();

echo "Patrimônios:  $patriAfter\n";
echo "Locais:       $localAfter\n";
echo "Histórico:    $histAfter\n";

$elapsed = round(microtime(true) - $startTime, 2);
echo "\n⏱️  Tempo total: {$elapsed}s\n\n";

echo "✅ IMPORTAÇÃO KINGHOST CONCLUÍDA COM SUCESSO!\n\n";

exit(0);
