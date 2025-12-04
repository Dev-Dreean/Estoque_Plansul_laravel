#!/usr/bin/env php
<?php
/**
 * Importação Plansul - KingHost Production
 * Parser inteligente para arquivos em formato de relatório
 */

set_time_limit(600);
ini_set('memory_limit', '512M');
error_reporting(E_ALL);

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  IMPORTAÇÃO PLANSUL - KINGHOST PRODUCTION                ║\n";
echo "║  " . date('d/m/Y H:i:s') . "                                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$start = microtime(true);

// Conectar ao banco
$env = [];
foreach (file(__DIR__ . '/../.env') as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') continue;
    @list($k, $v) = explode('=', $line, 2);
    if ($k && $v) $env[trim($k)] = trim($v, '"\'');
}

try {
    $pdo = new PDO(
        sprintf("mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4", 
            $env['DB_HOST'], $env['DB_PORT']??3306, $env['DB_DATABASE']),
        $env['DB_USERNAME'], $env['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Conectado: {$env['DB_DATABASE']}@{$env['DB_HOST']}\n\n";
} catch (Exception $e) {
    die("❌ Erro de conexão: " . $e->getMessage() . "\n");
}

// ============================================================================
// ETAPA 1: IMPORTAR LOCAIS
// ============================================================================
echo "🏗️  ETAPA 1: IMPORTANDO LOCAIS\n";
echo "════════════════════════════════════════════════════════════\n";

$file = __DIR__ . '/../storage/imports/Novo import/LocalProjeto.TXT';
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    // Identificar posições das colunas pelo cabeçalho
    $header = $lines[0];
    $colPos = [
        'CDLOCAL' => strpos($header, 'CDLOCAL'),
        'DELOCAL' => strpos($header, 'DELOCAL'),
        'CDFANTASIA' => strpos($header, 'CDFANTASIA')
    ];
    
    $pdo->beginTransaction();
    $created = $updated = 0;
    
    for ($i = 2; $i < count($lines); $i++) {
        $line = $lines[$i];
        if (strpos($line, '===') !== false) continue;
        
        // Converter encoding
        if (!mb_check_encoding($line, 'UTF-8')) {
            $line = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $line);
        }
        
        // Extrair dados por posição
        $parts = preg_split('/\s{2,}/', trim($line));
        if (count($parts) < 3) continue;
        
        $cdlocal = trim($parts[0]);
        $delocal = trim($parts[1]);
        $cdprojeto = trim($parts[count($parts) - 1]);
        
        if (empty($cdlocal)) continue;
        
        $stmt = $pdo->prepare("
            INSERT INTO locais_projeto (cdlocal, descricao, cdprojeto, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                descricao = VALUES(descricao),
                cdprojeto = VALUES(cdprojeto),
                updated_at = NOW()
        ");
        
        try {
            $stmt->execute([$cdlocal, $delocal, $cdprojeto]);
            if ($stmt->rowCount() == 1) $created++;
            else $updated++;
        } catch (Exception $e) {
            echo "  ⚠️  Erro linha $i: " . substr($e->getMessage(), 0, 50) . "\n";
        }
    }
    
    $pdo->commit();
    echo "✅ Locais: $created novos + $updated atualizados\n\n";
} else {
    echo "⚠️  Arquivo não encontrado\n\n";
}

// ============================================================================
// ETAPA 2: IMPORTAR PATRIMÔNIOS
// ============================================================================
echo "🏛️  ETAPA 2: IMPORTANDO PATRIMÔNIOS\n";
echo "════════════════════════════════════════════════════════════\n";

$file = __DIR__ . '/../storage/imports/Novo import/Patrimonio.txt';
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    $pdo->beginTransaction();
    $created = $updated = $errors = 0;
    
    for ($i = 2; $i < count($lines); $i++) {
        $line = $lines[$i];
        if (strpos($line, '===') !== false) continue;
        
        if (!mb_check_encoding($line, 'UTF-8')) {
            $line = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $line);
        }
        
        // Split por múltiplos espaços
        $parts = preg_split('/\s{2,}/', trim($line));
        if (count($parts) < 9) continue;
        
        $nupatrimonio = trim($parts[0]);
        $depatrimonio = trim($parts[1]);
        $cdfunc = trim($parts[2] ?? '');
        $cdobjeto = trim($parts[3] ?? '');
        $cdlocal = trim($parts[4] ?? '');
        $usuario = trim($parts[5] ?? 'SISTEMA');
        $cdprojeto = trim($parts[6] ?? '');
        $dtinclusao = trim($parts[7] ?? '');
        $situacao = trim($parts[8] ?? '');
        
        // Normalizar data
        if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dtinclusao, $m)) {
            $dtinclusao = "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        
        if (empty($nupatrimonio)) continue;
        
        $stmt = $pdo->prepare("
            INSERT INTO patr (
                NUPATRIMONIO, DEPATRIMONIO, CDFUNC, CDOBJETO, CDLOCAL,
                USUARIO, CDPROJETO, DTINCLUSAO, SITUACAO, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                DEPATRIMONIO = VALUES(DEPATRIMONIO),
                CDFUNC = VALUES(CDFUNC),
                CDOBJETO = VALUES(CDOBJETO),
                CDLOCAL = VALUES(CDLOCAL),
                USUARIO = VALUES(USUARIO),
                CDPROJETO = VALUES(CDPROJETO),
                DTINCLUSAO = VALUES(DTINCLUSAO),
                SITUACAO = VALUES(SITUACAO),
                updated_at = NOW()
        ");
        
        try {
            $stmt->execute([
                $nupatrimonio, $depatrimonio, $cdfunc, $cdobjeto, $cdlocal,
                $usuario, $cdprojeto, $dtinclusao, $situacao
            ]);
            if ($stmt->rowCount() == 1) $created++;
            else $updated++;
        } catch (Exception $e) {
            $errors++;
            if ($errors < 5) {
                echo "  ⚠️  Erro linha $i: " . substr($e->getMessage(), 0, 60) . "\n";
            }
        }
        
        if (($created + $updated) % 1000 == 0) {
            echo "  📊 Processados: " . ($created + $updated) . " (novos: $created | atualizados: $updated)\n";
        }
    }
    
    $pdo->commit();
    echo "✅ Patrimônios: $created novos + $updated atualizados ($errors erros)\n\n";
} else {
    echo "⚠️  Arquivo não encontrado\n\n";
}

// ============================================================================
// ETAPA 3: IMPORTAR HISTÓRICO
// ============================================================================
echo "📜 ETAPA 3: IMPORTANDO HISTÓRICO\n";
echo "════════════════════════════════════════════════════════════\n";

$file = __DIR__ . '/../storage/imports/Novo import/Hist_movpatr.TXT';
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    $pdo->beginTransaction();
    $created = $errors = 0;
    $tipoMap = ['I' => 'INCLUSAO', 'A' => 'ALTERACAO', 'E' => 'EXCLUSAO', 'M' => 'MOVIMENTACAO'];
    
    for ($i = 2; $i < count($lines); $i++) {
        $line = $lines[$i];
        if (strpos($line, '===') !== false) continue;
        
        if (!mb_check_encoding($line, 'UTF-8')) {
            $line = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $line);
        }
        
        $parts = preg_split('/\s{2,}/', trim($line));
        if (count($parts) < 5) continue;
        
        $nupatrim = trim($parts[0]);
        $nuproj = trim($parts[1]);
        $dtmovi = trim($parts[2]);
        $flmov = trim($parts[3]);
        $usuario = trim($parts[4] ?? 'SISTEMA');
        
        // Normalizar data
        if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dtmovi, $m)) {
            $dtmovi = "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        
        $tipo = $tipoMap[$flmov] ?? 'ALTERACAO';
        
        if (empty($nupatrim)) continue;
        
        $stmt = $pdo->prepare("
            INSERT INTO movpartr (NUPATRIM, NUPROJ, DTMOVI, TIPO, USUARIO, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        try {
            $stmt->execute([$nupatrim, $nuproj, $dtmovi, $tipo, $usuario]);
            $created++;
        } catch (Exception $e) {
            $errors++;
        }
        
        if ($created % 500 == 0) {
            echo "  📊 Processados: $created\n";
        }
    }
    
    $pdo->commit();
    echo "✅ Histórico: $created novos ($errors duplicados/erros)\n\n";
} else {
    echo "⚠️  Arquivo não encontrado\n\n";
}

// ============================================================================
// RESULTADO FINAL
// ============================================================================
echo "📊 CONTAGEM FINAL\n";
echo "════════════════════════════════════════════════════════════\n";

$patri = $pdo->query("SELECT COUNT(*) FROM patr")->fetchColumn();
$locais = $pdo->query("SELECT COUNT(*) FROM locais_projeto")->fetchColumn();
$hist = $pdo->query("SELECT COUNT(*) FROM movpartr")->fetchColumn();
$patriUser = $pdo->query("SELECT COUNT(*) FROM patr WHERE USUARIO IS NOT NULL AND USUARIO != ''")->fetchColumn();

echo "Patrimônios:      $patri\n";
echo "  Com usuário:    $patriUser (" . round($patriUser/$patri*100, 1) . "%)\n";
echo "Locais:           $locais\n";
echo "Histórico:        $hist\n";

$elapsed = round(microtime(true) - $start, 2);
echo "\n⏱️  Tempo total: {$elapsed}s\n";
echo "\n✅ IMPORTAÇÃO CONCLUÍDA COM SUCESSO!\n\n";

exit(0);
