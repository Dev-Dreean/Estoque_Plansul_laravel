<?php
/**
 * SCRIPT: Sincronizar plansul263 (Projetos) + plansul104 (Funcionários) → KingHost
 * 
 * OBJETIVO:
 *   Puxar TODAS as atualizações de dois bancos remotos:
 *   - plansul263 (tabfant → tabelas de projetos)
 *   - plansul104 (funcionarios → tabelas de funcionários)
 *   E sincronizar com KingHost (plansul04)
 * 
 * TABELAS SINCRONIZADAS:
 *   1. tabfant (projetos) - plansul263 → KingHost
 *   2. funcionarios (funcionários) - plansul104 → KingHost
 * 
 * DIREÇÃO:
 *   plansul263 → KingHost (upsert by id/CDPROJETO)
 *   plansul104 → KingHost (upsert by CDMATRFUNCIONARIO)
 * 
 * USO:
 *   php scripts/sync_kinghost_full_plansul263_104.php --dry-run
 *   php scripts/sync_kinghost_full_plansul263_104.php
 * 
 * COM SSH (remoto):
 *   ssh plansul@ftp.plansul.info "cd ~/www/estoque-laravel && php82 scripts/sync_kinghost_full_plansul263_104.php --dry-run"
 *   ssh plansul@ftp.plansul.info "cd ~/www/estoque-laravel && php82 scripts/sync_kinghost_full_plansul263_104.php"
 * 
 * LOGS:
 *   storage/logs/sync_kinghost_plansul263_104_YYYY-MM-DD_HHmmss.log
 */

// ═══════════════════════════════════════════════════════════════
// SETUP
// ═══════════════════════════════════════════════════════════════

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Carregar .env
try {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
} catch (Exception $e) {
    echo "⚠️ Usando variáveis de ambiente do servidor\n";
}

// ═══════════════════════════════════════════════════════════════
// CONFIGURAÇÃO
// ═══════════════════════════════════════════════════════════════

$isDryRun = in_array('--dry-run', $argv);
$timestamp = date('Y-m-d_His');
$logPath = __DIR__ . "/../storage/logs/sync_kinghost_plansul263_104_{$timestamp}.log";

// Criar diretório se não existir
@mkdir(dirname($logPath), 0755, true);

$logFile = fopen($logPath, 'a');

// ═══════════════════════════════════════════════════════════════
// FUNÇÕES AUXILIARES
// ═══════════════════════════════════════════════════════════════

function log_msg($msg, $level = '✓') {
    global $logFile, $isDryRun;
    $ts = date('Y-m-d H:i:s');
    $line = "[$ts] $level $msg";
    fwrite($logFile, $line . "\n");
    echo $line . "\n";
}

function env_get($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function conectar_pdo($host, $user, $pass, $db, $charset = 'utf8mb4') {
    try {
        $pdo = new PDO(
            "mysql:host={$host};port=3306;dbname={$db};charset={$charset}",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
            ]
        );
        return $pdo;
    } catch (Exception $e) {
        return null;
    }
}

function contar_registros($pdo, $tabela) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM {$tabela}");
        return $stmt->fetch()['cnt'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

function buscar_campos($pdo, $tabela) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM {$tabela}");
        $cols = [];
        while ($row = $stmt->fetch()) {
            $cols[] = $row['Field'];
        }
        return $cols;
    } catch (Exception $e) {
        return [];
    }
}

// ═══════════════════════════════════════════════════════════════
// INICIALIZAÇÃO
// ═══════════════════════════════════════════════════════════════

log_msg("═══════════════════════════════════════════════════════════════");
log_msg("SINCRONIZAÇÃO: plansul263 + plansul104 → KingHost", "🔄");
log_msg("═══════════════════════════════════════════════════════════════");
log_msg("Timestamp: " . date('Y-m-d H:i:s'));
log_msg("Modo: " . ($isDryRun ? "DRY-RUN (simulação)" : "PRODUÇÃO"));
log_msg("");

// ═══════════════════════════════════════════════════════════════
// CONEXÕES
// ═══════════════════════════════════════════════════════════════

log_msg("Conectando aos bancos...");

// plansul263 (Projetos)
$plansul263 = conectar_pdo(
    env_get('TABFANTASIA_SOURCE_HOST', 'mysql.plansul2.kinghost.net'),
    env_get('TABFANTASIA_SOURCE_USER', 'plansul263'),
    env_get('TABFANTASIA_SOURCE_PASS', 'plansul263'),
    env_get('TABFANTASIA_SOURCE_DB', 'plansul263')
);

if (!$plansul263) {
    log_msg("Erro ao conectar em plansul263", "❌");
    fclose($logFile);
    exit(1);
}
log_msg("✅ Conectado: plansul263 (Projetos)");

// plansul104 (Funcionários)
$plansul104 = conectar_pdo(
    env_get('FUNCIONARIOS_SOURCE_HOST', 'mysql.plansul2.kinghost.net'),
    env_get('FUNCIONARIOS_SOURCE_USER', 'plansul104'),
    env_get('FUNCIONARIOS_SOURCE_PASS', 'plansul104'),
    env_get('FUNCIONARIOS_SOURCE_DB', 'plansul104')
);

if (!$plansul104) {
    log_msg("Erro ao conectar em plansul104", "❌");
    fclose($logFile);
    exit(1);
}
log_msg("✅ Conectado: plansul104 (Funcionários)");

// KingHost (Destino)
$kinghost = conectar_pdo(
    'mysql07-farm10.kinghost.net',
    'plansul004_add2',
    'A33673170a',
    'plansul04'
);

if (!$kinghost) {
    log_msg("Erro ao conectar em KingHost (plansul04)", "❌");
    fclose($logFile);
    exit(1);
}
log_msg("✅ Conectado: KingHost (plansul04)");
log_msg("");

// ═══════════════════════════════════════════════════════════════
// SINCRONIZAÇÃO 1: TABFANT (PROJETOS)
// ═══════════════════════════════════════════════════════════════

log_msg("📋 SINCRONIZAÇÃO 1: TABFANT (Projetos - plansul263 → KingHost)");

try {
    $countSource = contar_registros($plansul263, 'tabfant');
    $countDest = contar_registros($kinghost, 'tabfant');
    
    log_msg("   Origem (plansul263): $countSource registros");
    log_msg("   Destino (KingHost): $countDest registros");
    
    $stmt = $plansul263->query("SELECT * FROM tabfant ORDER BY id ASC");
    $registros = $stmt->fetchAll();
    
    $added = 0;
    $updated = 0;
    $errors = 0;
    
    foreach ($registros as $row) {
        try {
            // Preparar dados
            $id = $row['id'];
            $cdprojeto = $row['CDPROJETO'] ?? null;
            $nomeprojeto = $row['NOMEPROJETO'] ?? null;
            
            // Verificar se existe
            $checkStmt = $kinghost->prepare("SELECT id FROM tabfant WHERE id = ?");
            $checkStmt->execute([$id]);
            $exists = $checkStmt->rowCount() > 0;
            
            if ($exists) {
                // UPDATE
                if (!$isDryRun) {
                    $updateStmt = $kinghost->prepare(
                        "UPDATE tabfant SET CDPROJETO = ?, NOMEPROJETO = ? WHERE id = ?"
                    );
                    $updateStmt->execute([$cdprojeto, $nomeprojeto, $id]);
                }
                $updated++;
            } else {
                // INSERT
                if (!$isDryRun) {
                    $insertStmt = $kinghost->prepare(
                        "INSERT INTO tabfant (id, CDPROJETO, NOMEPROJETO) VALUES (?, ?, ?)"
                    );
                    $insertStmt->execute([$id, $cdprojeto, $nomeprojeto]);
                }
                $added++;
            }
        } catch (Exception $e) {
            log_msg("   ⚠️ Erro ao processar projeto id={$id}: " . $e->getMessage(), "⚠️");
            $errors++;
        }
    }
    
    log_msg("   ✅ Resultado: +$added novos, ~$updated atualizados, $errors erros");
    log_msg("");
    
} catch (Exception $e) {
    log_msg("ERRO na sincronização TABFANT: " . $e->getMessage(), "❌");
}

// ═══════════════════════════════════════════════════════════════
// SINCRONIZAÇÃO 2: FUNCIONARIOS
// ═══════════════════════════════════════════════════════════════

log_msg("👥 SINCRONIZAÇÃO 2: FUNCIONARIOS (plansul104 → KingHost)");

try {
    $countSource = contar_registros($plansul104, 'funcionarios');
    $countDest = contar_registros($kinghost, 'funcionarios');
    
    log_msg("   Origem (plansul104): $countSource registros");
    log_msg("   Destino (KingHost): $countDest registros");
    
    $stmt = $plansul104->query("SELECT * FROM funcionarios ORDER BY CDMATRFUNCIONARIO ASC");
    $registros = $stmt->fetchAll();
    
    $added = 0;
    $updated = 0;
    $errors = 0;
    
    // Campos a sincronizar
    $campos_sync = [
        'CDMATRFUNCIONARIO', 'NMFUNCIONARIO', 'DTADMISSAO', 'CDCARGO', 
        'CODFIL', 'UFPROJ', 'DESETOR', 'DESITUACAO'
    ];
    
    foreach ($registros as $row) {
        try {
            $cdmatr = $row['CDMATRFUNCIONARIO'];
            
            // Verificar se existe
            $checkStmt = $kinghost->prepare("SELECT CDMATRFUNCIONARIO FROM funcionarios WHERE CDMATRFUNCIONARIO = ?");
            $checkStmt->execute([$cdmatr]);
            $exists = $checkStmt->rowCount() > 0;
            
            if ($exists) {
                // UPDATE - apenas campos existentes
                $updates = [];
                $valores = [];
                foreach ($campos_sync as $col) {
                    if ($col !== 'CDMATRFUNCIONARIO' && isset($row[$col])) {
                        $updates[] = "$col = ?";
                        $valores[] = $row[$col];
                    }
                }
                
                if (!empty($updates)) {
                    if (!$isDryRun) {
                        $sql = "UPDATE funcionarios SET " . implode(", ", $updates) . " WHERE CDMATRFUNCIONARIO = ?";
                        $valores[] = $cdmatr;
                        $updateStmt = $kinghost->prepare($sql);
                        $updateStmt->execute($valores);
                    }
                    $updated++;
                }
            } else {
                // INSERT
                $cols = ['CDMATRFUNCIONARIO'];
                $vals = [$cdmatr];
                $placeholders = ['?'];
                
                foreach ($campos_sync as $col) {
                    if ($col !== 'CDMATRFUNCIONARIO' && isset($row[$col])) {
                        $cols[] = $col;
                        $vals[] = $row[$col];
                        $placeholders[] = '?';
                    }
                }
                
                if (!$isDryRun) {
                    $sql = "INSERT INTO funcionarios (" . implode(", ", $cols) . ") 
                            VALUES (" . implode(", ", $placeholders) . ")";
                    $insertStmt = $kinghost->prepare($sql);
                    $insertStmt->execute($vals);
                }
                $added++;
            }
        } catch (Exception $e) {
            log_msg("   ⚠️ Erro ao processar funcionário cdmatr={$cdmatr}: " . $e->getMessage(), "⚠️");
            $errors++;
        }
    }
    
    log_msg("   ✅ Resultado: +$added novos, ~$updated atualizados, $errors erros");
    log_msg("");
    
} catch (Exception $e) {
    log_msg("ERRO na sincronização FUNCIONARIOS: " . $e->getMessage(), "❌");
}

// ═══════════════════════════════════════════════════════════════
// FINALIZAÇÃO
// ═══════════════════════════════════════════════════════════════

log_msg("═══════════════════════════════════════════════════════════════");
if ($isDryRun) {
    log_msg("✅ DRY-RUN CONCLUÍDO (nenhum dado foi alterado)", "✅");
} else {
    log_msg("✅ SINCRONIZAÇÃO CONCLUÍDA COM SUCESSO", "✅");
}
log_msg("═══════════════════════════════════════════════════════════════");
log_msg("Log salvo: $logPath");

fclose($logFile);

echo "\n📁 Log completo: $logPath\n\n";
