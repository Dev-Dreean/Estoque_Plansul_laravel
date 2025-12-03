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
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    
    $pdo->beginTransaction();
    $created = $updated = $errors = 0;
    
    // Cada registro ocupa 3 linhas: linha1 (dados), linha2 (descrição), linha3 (usuario/projeto)
    for ($i = 2; $i < count($lines); $i += 3) {
        if (!isset($lines[$i], $lines[$i+1], $lines[$i+2])) break;
        
        $linha1 = $lines[$i];
        $linha2 = $lines[$i+1];
        $linha3 = $lines[$i+2];
        
        if (strpos($linha1, '===') !== false) continue;
        
        // Converter encoding
        if (!mb_check_encoding($linha1, 'UTF-8')) {
            $linha1 = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $linha1);
            $linha2 = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $linha2);
            $linha3 = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $linha3);
        }
        
        // Extrair dados da linha 1 (colunas fixas)
        $nupatrimonio = trim(substr($linha1, 0, 16));
        $situacao = trim(substr($linha1, 16, 35));
        $marca = trim(substr($linha1, 51, 35));
        $cdlocal = trim(substr($linha1, 86, 11));
        $modelo = trim(substr($linha1, 97, 35));
        $cor = trim(substr($linha1, 132, 20));
        $dtaquisicao = trim(substr($linha1, 152, 11));
        
        // Extrair descrição da linha 2
        $depatrimonio = trim($linha2);
        
        // Extrair dados da linha 3 (colunas fixas)
        $cdfunc = trim(substr($linha3, 0, 18));
        $cdprojeto = trim(substr($linha3, 18, 13));
        $nudocfiscal = trim(substr($linha3, 31, 15));
        $usuario = trim(substr($linha3, 46, 15));
        $dtoperacao = trim(substr($linha3, 61, 14));
        $numof = trim(substr($linha3, 75, 10));
        $cdobjeto = trim(substr($linha3, 85, 13));
        
        // Substituir <null> por vazio
        $situacao = ($situacao === '<null>') ? '' : $situacao;
        $marca = ($marca === '<null>') ? '' : $marca;
        $cor = ($cor === '<null>') ? '' : $cor;
        $usuario = ($usuario === '<null>' || empty($usuario)) ? 'SISTEMA' : $usuario;
        
        // Normalizar data
        if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dtaquisicao, $m)) {
            $dtaquisicao = "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        
        if (empty($nupatrimonio) || !is_numeric($nupatrimonio)) continue;
        
        $stmt = $pdo->prepare("
            INSERT INTO patr (
                NUPATRIMONIO, DEPATRIMONIO, SITUACAO, MARCA, MODELO, COR,
                CDLOCAL, CDFUNC, CDPROJETO, CDOBJETO, USUARIO,
                DTINCLUSAO, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                DEPATRIMONIO = VALUES(DEPATRIMONIO),
                SITUACAO = VALUES(SITUACAO),
                MARCA = VALUES(MARCA),
                MODELO = VALUES(MODELO),
                COR = VALUES(COR),
                CDLOCAL = VALUES(CDLOCAL),
                CDFUNC = VALUES(CDFUNC),
                CDPROJETO = VALUES(CDPROJETO),
                CDOBJETO = VALUES(CDOBJETO),
                USUARIO = VALUES(USUARIO),
                DTINCLUSAO = VALUES(DTINCLUSAO),
                updated_at = NOW()
        ");
        
        try {
            $stmt->execute([
                $nupatrimonio, $depatrimonio, $situacao, $marca, $modelo, $cor,
                $cdlocal, $cdfunc, $cdprojeto, $cdobjeto, $usuario, $dtaquisicao
            ]);
            if ($stmt->rowCount() == 1) $created++;
            else $updated++;
        } catch (Exception $e) {
            $errors++;
            if ($errors < 10) {
                echo "  ⚠️  Erro patrimônio $nupatrimonio: " . substr($e->getMessage(), 0, 60) . "\n";
            }
        }
        
        if (($created + $updated) > 0 && ($created + $updated) % 1000 == 0) {
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
