<?php
/**
 * SCRIPT: Sincronizar Funcionários e Projetos para KingHost
 * Sincroniza dados do banco local (cadastros_plansul) para KingHost
 * Uso: php scripts/sincronizar_funcionarios_projetos_kinghost.php [--dry-run]
 */

// Carregar .env via Dotenv do Laravel
$envPath = __DIR__ . '/../.env';
try {
    require __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(dirname($envPath));
    $dotenv->load();
} catch (Exception $e) {
    // Fallback para leitura manual se Dotenv falhar
    if (file_exists($envPath)) {
        $lines = file($envPath);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim(str_replace(['"', "'"], '', $val));
                $_ENV[$key] = $val;
                putenv("$key=$val");
            }
        }
    }
}

// Config
$isDryRun = in_array('--dry-run', $argv);
$now = new DateTime();
$logPath = 'storage/logs/sincronizacao_' . $now->format('Y-m-d_Hi') . '.log';

// Criar log dir
$logDir = dirname($logPath);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logFile = fopen($logPath, 'a');

// Helper
function log_msg($msg, $type = 'INFO') {
    global $logFile;
    $ts = (new DateTime())->format('Y-m-d H:i:s');
    $line = "[$ts] $type: $msg\n";
    fwrite($logFile, $line);
    echo $line;
}

function env_get($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function stats_str($s) {
    return "Add={$s['add']}, Update={$s['update']}, Error={$s['error']}";
}

// ===== CONEXOES =====
log_msg("═══════════════════════════════════════════════");
log_msg("INICIANDO - " . (new DateTime())->format('Y-m-d H:i:s'));
log_msg("Mode: " . ($isDryRun ? "DRY-RUN" : "PRODUCAO"));
log_msg("═══════════════════════════════════════════════");

// Local
try {
    $localDb = new PDO(
        'mysql:host=' . env_get('DB_HOST', '127.0.0.1') . 
        ';port=' . env_get('DB_PORT', 3306) . 
        ';dbname=' . env_get('DB_DATABASE', 'cadastros_plansul') .
        ';charset=utf8mb4',
        env_get('DB_USERNAME', 'root'),
        env_get('DB_PASSWORD', ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    log_msg("✅ Banco local conectado");
} catch (Exception $e) {
    log_msg("❌ Erro banco local: " . $e->getMessage(), 'ERRO');
    fclose($logFile);
    exit(1);
}

// KingHost Funcionarios
try {
    $kh_func = new PDO(
        'mysql:host=' . env_get('FUNCIONARIOS_SOURCE_HOST', 'mysql.plansul2.kinghost.net') . 
        ';port=' . env_get('FUNCIONARIOS_SOURCE_PORT', 3306) . 
        ';dbname=' . env_get('FUNCIONARIOS_SOURCE_DB', 'plansul104') .
        ';charset=utf8mb4',
        env_get('FUNCIONARIOS_SOURCE_USER'),
        env_get('FUNCIONARIOS_SOURCE_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    log_msg("✅ KingHost Funcionarios conectado");
} catch (Exception $e) {
    log_msg("❌ Erro KingHost Func: " . $e->getMessage(), 'ERRO');
    fclose($logFile);
    exit(1);
}

// KingHost Projetos
try {
    $kh_proj = new PDO(
        'mysql:host=' . env_get('TABFANTASIA_SOURCE_HOST', 'mysql.plansul2.kinghost.net') . 
        ';port=' . env_get('TABFANTASIA_SOURCE_PORT', 3306) . 
        ';dbname=' . env_get('TABFANTASIA_SOURCE_DB', 'plansul263') .
        ';charset=utf8mb4',
        env_get('TABFANTASIA_SOURCE_USER'),
        env_get('TABFANTASIA_SOURCE_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    log_msg("✅ KingHost Projetos conectado");
} catch (Exception $e) {
    log_msg("❌ Erro KingHost Proj: " . $e->getMessage(), 'ERRO');
    fclose($logFile);
    exit(1);
}

// ===== FUNCIONARIOS =====
log_msg("\n📋 Sincronizando Funcionarios...");
$sf = ['add' => 0, 'update' => 0, 'error' => 0];

try {
    $funcs = $localDb->query("SELECT * FROM funcionarios")->fetchAll();
    log_msg("  Encontrados: " . count($funcs));
    
    foreach ($funcs as $f) {
        try {
            $exists = $kh_func->prepare("SELECT CDMATRFUNCIONARIO FROM funcionarios WHERE CDMATRFUNCIONARIO = ?")->execute([$f['CDMATRFUNCIONARIO']]);
            $has = $kh_func->query("SELECT COUNT(*) FROM funcionarios WHERE CDMATRFUNCIONARIO = '{$f['CDMATRFUNCIONARIO']}'")->fetch();
            
            if ($has['COUNT(*)'] > 0) {
                $kh_func->prepare("UPDATE funcionarios SET NMFUNCIONARIO=?, DTADMISSAO=?, CDCARGO=?, CODFIL=?, UFPROJ=? WHERE CDMATRFUNCIONARIO=?")->execute([
                    $f['NMFUNCIONARIO'] ?? '', $f['DTADMISSAO'] ?? null, $f['CDCARGO'] ?? null, $f['CODFIL'] ?? null, $f['UFPROJ'] ?? null, $f['CDMATRFUNCIONARIO']
                ]);
                $sf['update']++;
            } else {
                $kh_func->prepare("INSERT INTO funcionarios (CDMATRFUNCIONARIO,NMFUNCIONARIO,DTADMISSAO,CDCARGO,CODFIL,UFPROJ) VALUES (?,?,?,?,?,?)")->execute([
                    $f['CDMATRFUNCIONARIO'], $f['NMFUNCIONARIO'] ?? '', $f['DTADMISSAO'] ?? null, $f['CDCARGO'] ?? null, $f['CODFIL'] ?? null, $f['UFPROJ'] ?? null
                ]);
                $sf['add']++;
            }
        } catch (Exception $e) {
            $sf['error']++;
            log_msg("  ⚠️ Erro {$f['CDMATRFUNCIONARIO']}: " . substr($e->getMessage(), 0, 50), 'AVISO');
        }
    }
    log_msg("  ✅ " . stats_str($sf));
} catch (Exception $e) {
    log_msg("❌ Erro Funcionarios: " . $e->getMessage(), 'ERRO');
    fclose($logFile);
    exit(1);
}

// ===== PROJETOS =====
log_msg("\n📋 Sincronizando Projetos...");
$sp = ['add' => 0, 'update' => 0, 'error' => 0];

try {
    $projs = $localDb->query("SELECT * FROM tabfant")->fetchAll();
    log_msg("  Encontrados: " . count($projs));
    
    foreach ($projs as $p) {
        try {
            $has = $kh_proj->query("SELECT COUNT(*) FROM tabfant WHERE id = {$p['id']}")->fetch();
            
            if ($has['COUNT(*)'] > 0) {
                $kh_proj->prepare("UPDATE tabfant SET NOMEPROJETO=?, CDPROJETO=?, LOCAL=?, UF=? WHERE id=?")->execute([
                    $p['NOMEPROJETO'] ?? '', $p['CDPROJETO'] ?? null, $p['LOCAL'] ?? null, $p['UF'] ?? null, $p['id']
                ]);
                $sp['update']++;
            } else {
                $kh_proj->prepare("INSERT INTO tabfant (id,NOMEPROJETO,CDPROJETO,LOCAL,UF) VALUES (?,?,?,?,?)")->execute([
                    $p['id'], $p['NOMEPROJETO'] ?? '', $p['CDPROJETO'] ?? null, $p['LOCAL'] ?? null, $p['UF'] ?? null
                ]);
                $sp['add']++;
            }
        } catch (Exception $e) {
            $sp['error']++;
            log_msg("  ⚠️ Erro {$p['id']}: " . substr($e->getMessage(), 0, 50), 'AVISO');
        }
    }
    log_msg("  ✅ " . stats_str($sp));
} catch (Exception $e) {
    log_msg("❌ Erro Projetos: " . $e->getMessage(), 'ERRO');
    fclose($logFile);
    exit(1);
}

// ===== VALIDACAO =====
log_msg("\n🔍 Validando...");
try {
    $loc_f = $localDb->query("SELECT COUNT(*) as cnt FROM funcionarios")->fetch();
    $kh_f = $kh_func->query("SELECT COUNT(*) as cnt FROM funcionarios")->fetch();
    
    $loc_p = $localDb->query("SELECT COUNT(*) as cnt FROM tabfant")->fetch();
    $kh_p = $kh_proj->query("SELECT COUNT(*) as cnt FROM tabfant")->fetch();
    
    log_msg("  Funcionarios: Local=" . $loc_f['cnt'] . " vs KH=" . $kh_f['cnt'] . " [" . ($loc_f['cnt'] === $kh_f['cnt'] ? "✅" : "⚠️") . "]");
    log_msg("  Projetos: Local=" . $loc_p['cnt'] . " vs KH=" . $kh_p['cnt'] . " [" . ($loc_p['cnt'] === $kh_p['cnt'] ? "✅" : "⚠️") . "]");
} catch (Exception $e) {
    log_msg("❌ Erro validacao: " . $e->getMessage(), 'ERRO');
}

// ===== FINAL =====
log_msg("\n═══════════════════════════════════════════════");
log_msg("✅ CONCLUIDO", 'SUCESSO');
log_msg("  Func: " . stats_str($sf));
log_msg("  Proj: " . stats_str($sp));
log_msg("  Log: $logPath");
log_msg("═══════════════════════════════════════════════");

fclose($logFile);
echo "\n✅ Concluido. Log: $logPath\n";
