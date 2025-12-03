#!/usr/bin/env php
<?php

set_time_limit(600);
ini_set('memory_limit', '512M');

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║     IMPORTAÇÃO PLANSUL - KINGHOST v2                    ║\n";
echo "║     Data: " . date('d/m/Y H:i:s') . "                                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Parse .env
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
        $env['DB_USERNAME'],
        $env['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Conectado ao banco de dados\n\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

// Importar Locais
echo "🏗️  IMPORTANDO LOCAIS\n";
echo "════════════════════════════════════════════════════════════\n";

$file = __DIR__ . '/../storage/imports/Novo import/LocalProjeto.TXT';
if (file_exists($file)) {
    $pdo->beginTransaction();
    $count = 0;
    if ($h = fopen($file, 'r')) {
        fgets($h); fgets($h);
        while ($line = fgets($h)) {
            if (empty(trim($line))) continue;
            if (!mb_check_encoding($line, 'UTF-8')) 
                $line = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $line);
            
            $p = str_getcsv(trim($line), ';');
            if (count($p) < 3) continue;
            
            $stmt = $pdo->prepare("INSERT INTO locais_projeto (cdlocal, descricao, cdprojeto, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW()) 
                ON DUPLICATE KEY UPDATE descricao=VALUES(descricao), updated_at=NOW()");
            try {
                $stmt->execute([trim($p[0]), trim($p[1]), trim($p[2])]);
                $count++;
            } catch (Exception $e) {}
        }
        fclose($h);
    }
    $pdo->commit();
    echo "✅ Locais: $count processados\n\n";
}

// Importar Patrimônios
echo "🏛️  IMPORTANDO PATRIMÔNIOS\n";
echo "════════════════════════════════════════════════════════════\n";

$file = __DIR__ . '/../storage/imports/Novo import/Patrimonio.txt';
if (file_exists($file)) {
    $pdo->beginTransaction();
    $count = 0;
    if ($h = fopen($file, 'r')) {
        fgets($h); fgets($h);
        while ($line = fgets($h)) {
            if (empty(trim($line))) continue;
            if (!mb_check_encoding($line, 'UTF-8')) 
                $line = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $line);
            
            $p = str_getcsv(trim($line), ';');
            if (count($p) < 10) continue;
            
            $num = trim($p[0]);
            $des = trim($p[1]);
            $usr = trim($p[5] ?? 'SISTEMA');
            $proj = trim($p[6] ?? '');
            $dt = trim($p[7] ?? '');
            $sit = trim($p[8] ?? '');
            
            if (strpos($dt, '/') !== false) {
                $d = explode('/', $dt);
                if (count($d) == 3) $dt = $d[2] . '-' . sprintf('%02d', $d[1]) . '-' . sprintf('%02d', $d[0]);
            }
            
            $stmt = $pdo->prepare("INSERT INTO patr (NUPATRIMONIO, DEPATRIMONIO, USUARIO, CDPROJETO, DTINCLUSAO, SITUACAO, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE DEPATRIMONIO=VALUES(DEPATRIMONIO), USUARIO=VALUES(USUARIO), CDPROJETO=VALUES(CDPROJETO), DTINCLUSAO=VALUES(DTINCLUSAO), SITUACAO=VALUES(SITUACAO), updated_at=NOW()");
            try {
                $stmt->execute([$num, $des, $usr, $proj, $dt, $sit]);
                $count++;
            } catch (Exception $e) {}
            
            if ($count % 500 == 0) echo "  Processados: $count\n";
        }
        fclose($h);
    }
    $pdo->commit();
    echo "✅ Patrimônios: $count processados\n\n";
}

// Importar Histórico
echo "📜 IMPORTANDO HISTÓRICO\n";
echo "════════════════════════════════════════════════════════════\n";

$file = __DIR__ . '/../storage/imports/Novo import/Hist_movpatr.TXT';
if (file_exists($file)) {
    $pdo->beginTransaction();
    $count = 0;
    $tipoMap = ['I' => 'INCLUSAO', 'A' => 'ALTERACAO', 'E' => 'EXCLUSAO', 'M' => 'MOVIMENTACAO'];
    if ($h = fopen($file, 'r')) {
        fgets($h); fgets($h);
        while ($line = fgets($h)) {
            if (empty(trim($line))) continue;
            if (!mb_check_encoding($line, 'UTF-8')) 
                $line = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $line);
            
            $p = str_getcsv(trim($line), ';');
            if (count($p) < 6) continue;
            
            $num = trim($p[0]);
            $proj = trim($p[1]);
            $dt = trim($p[2]);
            $tipo = $tipoMap[trim($p[3])] ?? 'ALTERACAO';
            $usr = trim($p[4] ?? 'SISTEMA');
            
            if (strpos($dt, '/') !== false) {
                $d = explode('/', $dt);
                if (count($d) == 3) $dt = $d[2] . '-' . sprintf('%02d', $d[1]) . '-' . sprintf('%02d', $d[0]);
            }
            
            $stmt = $pdo->prepare("INSERT INTO movpartr (NUPATRIM, NUPROJ, DTMOVI, TIPO, USUARIO, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())");
            try {
                $stmt->execute([$num, $proj, $dt, $tipo, $usr]);
                $count++;
            } catch (Exception $e) {}
        }
        fclose($h);
    }
    $pdo->commit();
    echo "✅ Histórico: $count processados\n\n";
}

// Resultado
echo "📊 RESULTADO FINAL\n";
echo "════════════════════════════════════════════════════════════\n";
echo "Patrimônios: " . $pdo->query("SELECT COUNT(*) FROM patr")->fetchColumn() . "\n";
echo "Locais:      " . $pdo->query("SELECT COUNT(*) FROM locais_projeto")->fetchColumn() . "\n";
echo "Histórico:   " . $pdo->query("SELECT COUNT(*) FROM movpartr")->fetchColumn() . "\n";
echo "\n✅ CONCLUÍDO!\n\n";

exit(0);
