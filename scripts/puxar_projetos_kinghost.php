<?php
/**
 * SCRIPT: Puxar Projetos do KingHost (plansul263) para Local
 * 
 * DIREÇÃO: KingHost → Local (PULL)
 * Tabela KingHost: tabfant (plansul263)
 * Tabela Local: tabfant (cadastros_plansul)
 * 
 * MAPEAMENTO:
 *   KingHost               →  Local
 *   (Todas as colunas ou mapeamento específico conforme necessário)
 * 
 * USO:
 *   php scripts/puxar_projetos_kinghost.php --dry-run
 *   php scripts/puxar_projetos_kinghost.php
 * 
 * SEGURANÇA:
 *   - Modo dry-run testa antes de executar
 *   - Cria backup automático antes de alterações
 *   - Log detalhado em storage/logs/
 */

require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__ . '/../.env'));
$dotenv->load();

// ═════════════════════════════════════════════════════════════════════════
// CONFIGURAÇÃO
// ═════════════════════════════════════════════════════════════════════════

$isDryRun = in_array('--dry-run', $argv);
$now = new DateTime();
$logPath = 'storage/logs/puxar_projetos_' . $now->format('Y-m-d_Hi') . '.log';

function env_get($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function log_msg($msg, $type = 'INFO') {
    global $logPath;
    $ts = (new DateTime())->format('Y-m-d H:i:s');
    $line = "[$ts] $type: $msg\n";
    
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    global $logFile;
    if (!$logFile) {
        $logFile = fopen($logPath, 'a');
    }
    fwrite($logFile, $line);
    echo $line;
}

// ═════════════════════════════════════════════════════════════════════════
// CONEXÕES
// ═════════════════════════════════════════════════════════════════════════

log_msg("═══════════════════════════════════════════════════════════════");
log_msg("PUXANDO PROJETOS DE KINGHOST");
log_msg("═══════════════════════════════════════════════════════════════");
log_msg("Timestamp: " . $now->format('Y-m-d H:i:s'));
log_msg("Modo: " . ($isDryRun ? "DRY-RUN (simulação)" : "PRODUÇÃO"));
log_msg("");

// Local
try {
    $localDb = new PDO(
        'mysql:host=' . env_get('DB_HOST', '127.0.0.1') . 
        ';dbname=' . env_get('DB_DATABASE', 'cadastros_plansul') . ';charset=utf8mb4',
        env_get('DB_USERNAME', 'root'),
        env_get('DB_PASSWORD', ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    log_msg("✅ Banco local conectado (cadastros_plansul)");
} catch (Exception $e) {
    log_msg("❌ Erro banco local: " . $e->getMessage(), 'ERRO');
    exit(1);
}

// KingHost Projetos (tabfant)
try {
    $khDb = new PDO(
        'mysql:host=' . env_get('TABFANTASIA_SOURCE_HOST', 'mysql.plansul2.kinghost.net') . 
        ';dbname=' . env_get('TABFANTASIA_SOURCE_DB', 'plansul263') . ';charset=utf8mb4',
        env_get('TABFANTASIA_SOURCE_USER'),
        env_get('TABFANTASIA_SOURCE_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    log_msg("✅ KingHost projetos conectado (plansul263)");
} catch (Exception $e) {
    log_msg("❌ Erro KingHost: " . $e->getMessage(), 'ERRO');
    exit(1);
}

log_msg("");

// ═════════════════════════════════════════════════════════════════════════
// VALIDAÇÃO DE ESTRUTURAS
// ═════════════════════════════════════════════════════════════════════════

log_msg("📋 Validando estruturas...");

$localCols = $localDb->query("DESCRIBE tabfant")->fetchAll();
$localFields = array_column($localCols, 'Field');

log_msg("   Local: " . count($localFields) . " colunas - " . implode(', ', $localFields));

// KingHost - listar colunas disponíveis
$khCols = $khDb->query("DESCRIBE tabfant")->fetchAll();
$khFields = array_column($khCols, 'Field');

log_msg("   KingHost: " . count($khFields) . " colunas - " . implode(', ', $khFields));
log_msg("");

// ═════════════════════════════════════════════════════════════════════════
// CONTAR REGISTROS
// ═════════════════════════════════════════════════════════════════════════

log_msg("📊 Contando registros...");

$khCount = $khDb->query("SELECT COUNT(*) as cnt FROM tabfant")->fetch();
$localCount = $localDb->query("SELECT COUNT(*) as cnt FROM tabfant")->fetch();

$khTotal = (int)$khCount['cnt'];
$localTotal = (int)$localCount['cnt'];

log_msg("   KingHost: $khTotal registros");
log_msg("   Local: $localTotal registros");
log_msg("");

// ═════════════════════════════════════════════════════════════════════════
// BACKUP
// ═════════════════════════════════════════════════════════════════════════

if (!$isDryRun) {
    log_msg("📦 Criando backup...");
    
    $backupFile = 'archive/backups/tabfant_backup_' . $now->format('Y-m-d_Hi') . '.json';
    $backupDir = dirname($backupFile);
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $backup = [
        'timestamp' => $now->format('Y-m-d H:i:s'),
        'total_records' => $localTotal,
        'query' => "SELECT * FROM tabfant",
        'records' => $localDb->query("SELECT * FROM tabfant")->fetchAll()
    ];
    
    file_put_contents($backupFile, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    log_msg("   ✓ Backup criado: $backupFile");
    log_msg("");
}

// ═════════════════════════════════════════════════════════════════════════
// SINCRONIZAR (PULL)
// ═════════════════════════════════════════════════════════════════════════

log_msg("🔄 Sincronizando projetos...");

$query = "SELECT * FROM tabfant";

$projetos = $khDb->query($query)->fetchAll();
$total = count($projetos);
$updated = 0;
$inserted = 0;
$errors = 0;

log_msg("   Processando $total registros...");

if ($isDryRun) {
    // Amostra dos 5 primeiros
    log_msg("   [DRY-RUN] Amostra dos 5 primeiros registros:");
    foreach (array_slice($projetos, 0, 5) as $p) {
        $display = implode(', ', array_slice($p, 0, 3)) . ',...';
        log_msg("      • ID=" . $p['id'] . ": " . ($p[array_key_first($p)] ?? 'N/A'));
    }
} else {
    // Executar sincronização
    foreach ($projetos as $idx => $p) {
        try {
            $id = $p['id'] ?? null;
            
            if (empty($id)) {
                continue;
            }
            
            // Verificar se existe (usar a mesma chave primária de KingHost)
            $existsRow = $localDb->query("SELECT COUNT(*) as cnt FROM tabfant WHERE id = {$id}")->fetch();
            $exists = (int)$existsRow['cnt'] > 0;
            
            // Preparar valores (escapar para segurança)
            $values = [];
            $placeholders = [];
            $updates = [];
            
            foreach ($p as $col => $val) {
                if ($col === 'id') continue; // ID é chave primária
                
                // Verificar se coluna existe localmente
                if (!in_array($col, $localFields)) {
                    continue;
                }
                
                $values[$col] = $val;
                $placeholders[] = '?';
                $updates[] = "`{$col}`=?";
            }
            
            if ($exists) {
                // UPDATE
                $updateSql = "UPDATE tabfant SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $localDb->prepare($updateSql);
                $params = array_values($values);
                $params[] = $id;
                $stmt->execute($params);
                $updated++;
            } else {
                // INSERT
                $cols = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($values)));
                $placeholderStr = implode(', ', array_fill(0, count($values), '?'));
                $insertSql = "INSERT INTO tabfant (id, {$cols}) VALUES (?, {$placeholderStr})";
                $stmt = $localDb->prepare($insertSql);
                $params = [$id, ...array_values($values)];
                $stmt->execute($params);
                $inserted++;
            }
            
            // Log a cada 100
            if (($idx + 1) % 100 === 0) {
                log_msg("   ... processados " . ($idx + 1) . " de $total");
            }
            
        } catch (Exception $e) {
            $errors++;
            if ($errors <= 10) {
                log_msg("   ⚠️  Erro registro #" . ($idx + 1) . ": " . $e->getMessage(), 'AVISO');
            }
        }
    }
}

log_msg("");
log_msg("═══════════════════════════════════════════════════════════════");
log_msg("✅ SINCRONIZAÇÃO COMPLETA");
log_msg("═══════════════════════════════════════════════════════════════");

if ($isDryRun) {
    log_msg("[DRY-RUN] Nenhuma alteração foi feita");
} else {
    log_msg("Inseridos: $inserted");
    log_msg("Atualizados: $updated");
    log_msg("Erros: $errors");
    log_msg("Total processado: " . ($updated + $inserted) . " de $total");
}

log_msg("Log: $logPath");
log_msg("");

fclose($logFile);
exit(0);
