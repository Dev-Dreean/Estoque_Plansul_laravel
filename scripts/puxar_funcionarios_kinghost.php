<?php
/**
 * SCRIPT: Puxar Funcionários do KingHost (plansul104) para Local
 * 
 * DIREÇÃO: KingHost → Local (PULL)
 * Tabela KingHost: funcionarios (plansul104)
 * Tabela Local: funcionarios (cadastros_plansul)
 * 
 * MAPEAMENTO:
 *   KingHost               →  Local
 *   matricula              →  CDMATRFUNCIONARIO
 *   nome                   →  NMFUNCIONARIO
 *   dtadmissao             →  DTADMISSAO
 *   cargo                  →  CDCARGO
 *   estado                 →  UFPROJ
 *   (outras colunas ignoradas ou mapeadas conforme necessário)
 * 
 * USO:
 *   php scripts/puxar_funcionarios_kinghost.php --dry-run
 *   php scripts/puxar_funcionarios_kinghost.php
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
$logPath = 'storage/logs/puxar_funcionarios_' . $now->format('Y-m-d_Hi') . '.log';

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
log_msg("PUXANDO FUNCIONÁRIOS DE KINGHOST");
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

// KingHost Funcionários
try {
    $khDb = new PDO(
        'mysql:host=' . env_get('FUNCIONARIOS_SOURCE_HOST', 'mysql.plansul2.kinghost.net') . 
        ';dbname=' . env_get('FUNCIONARIOS_SOURCE_DB', 'plansul104') . ';charset=utf8mb4',
        env_get('FUNCIONARIOS_SOURCE_USER'),
        env_get('FUNCIONARIOS_SOURCE_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    log_msg("✅ KingHost funcionários conectado (plansul104)");
} catch (Exception $e) {
    log_msg("❌ Erro KingHost: " . $e->getMessage(), 'ERRO');
    exit(1);
}

log_msg("");

// ═════════════════════════════════════════════════════════════════════════
// VALIDAÇÃO DE ESTRUTURAS
// ═════════════════════════════════════════════════════════════════════════

log_msg("📋 Validando estruturas...");

$localCols = $localDb->query("DESCRIBE funcionarios")->fetchAll();
$localFields = array_column($localCols, 'Field');

$khCols = $khDb->query("DESCRIBE funcionarios")->fetchAll();
$khFields = array_column($khCols, 'Field');

log_msg("   Local: " . count($localFields) . " colunas");
log_msg("   KingHost: " . count($khFields) . " colunas");

// Verificar se campos esperados existem
$requiredLocal = ['CDMATRFUNCIONARIO', 'NMFUNCIONARIO', 'DTADMISSAO', 'CDCARGO', 'UFPROJ'];
$requiredKH = ['matricula', 'nome', 'cargo', 'dtadmissao', 'estado'];

$missingLocal = array_diff($requiredLocal, $localFields);
$missingKH = array_diff($requiredKH, $khFields);

if ($missingLocal) {
    log_msg("❌ Colunas faltando LOCAL: " . implode(', ', $missingLocal), 'ERRO');
    exit(1);
}

if ($missingKH) {
    log_msg("❌ Colunas faltando KINGHOST: " . implode(', ', $missingKH), 'ERRO');
    exit(1);
}

log_msg("   ✓ Estruturas OK");
log_msg("");

// ═════════════════════════════════════════════════════════════════════════
// CONTAR REGISTROS
// ═════════════════════════════════════════════════════════════════════════

log_msg("📊 Contando registros...");

$khCount = $khDb->query("SELECT COUNT(*) as cnt FROM funcionarios")->fetch();
$localCount = $localDb->query("SELECT COUNT(*) as cnt FROM funcionarios")->fetch();

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
    
    $backupFile = 'archive/backups/funcionarios_backup_' . $now->format('Y-m-d_Hi') . '.json';
    $backupDir = dirname($backupFile);
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $backup = [
        'timestamp' => $now->format('Y-m-d H:i:s'),
        'total_records' => $localTotal,
        'query' => "SELECT * FROM funcionarios",
        'records' => $localDb->query("SELECT * FROM funcionarios")->fetchAll()
    ];
    
    file_put_contents($backupFile, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    log_msg("   ✓ Backup criado: $backupFile");
    log_msg("");
}

// ═════════════════════════════════════════════════════════════════════════
// SINCRONIZAR (PULL)
// ═════════════════════════════════════════════════════════════════════════

log_msg("🔄 Sincronizando funcionários...");

$query = "
    SELECT 
        matricula,
        nome,
        cargo,
        dtadmissao,
        estado
    FROM funcionarios
    ORDER BY matricula
";

$funcs = $khDb->query($query)->fetchAll();
$total = count($funcs);
$updated = 0;
$inserted = 0;
$errors = 0;

log_msg("   Processando $total registros...");

if ($isDryRun) {
    // Amostra dos 5 primeiros
    log_msg("   [DRY-RUN] Amostra dos 5 primeiros registros:");
    foreach (array_slice($funcs, 0, 5) as $f) {
        log_msg("      • matricula={$f['matricula']}, nome={$f['nome']}, cargo={$f['cargo']}");
    }
} else {
    // Executar sincronização
    foreach ($funcs as $idx => $f) {
        try {
            $matricula = trim($f['matricula'] ?? '');
            $nome = trim($f['nome'] ?? '');
            $cargo = trim($f['cargo'] ?? '');
            $dtadm = trim($f['dtadmissao'] ?? '');
            $estado = trim($f['estado'] ?? '');
            
            if (empty($matricula)) {
                continue;
            }
            
            // Converter data se necessário
            $dtadmFormatted = null;
            if (!empty($dtadm)) {
                try {
                    // Assumir formato varchar(150) — pode ser YYYY-MM-DD ou outro
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dtadm)) {
                        $dtadmFormatted = substr($dtadm, 0, 10);
                    } elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $dtadm, $m)) {
                        $dtadmFormatted = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
                    }
                } catch (Exception $e) {
                    // Ignorar erro de data
                }
            }
            
            // Verificar se existe
            $exists = $localDb->prepare("SELECT COUNT(*) as cnt FROM funcionarios WHERE CDMATRFUNCIONARIO = ?")
                ->execute([$matricula]);
            $existsRow = $localDb->query("SELECT COUNT(*) as cnt FROM funcionarios WHERE CDMATRFUNCIONARIO = '{$matricula}' ESCAPE '\\''")->fetch();
            $exists = (int)$existsRow['cnt'] > 0;
            
            if ($exists) {
                // UPDATE
                $stmt = $localDb->prepare(
                    "UPDATE funcionarios SET NMFUNCIONARIO=?, DTADMISSAO=?, CDCARGO=?, UFPROJ=? WHERE CDMATRFUNCIONARIO=?"
                );
                $stmt->execute([$nome, $dtadmFormatted, $cargo, $estado, $matricula]);
                $updated++;
            } else {
                // INSERT
                $stmt = $localDb->prepare(
                    "INSERT INTO funcionarios (CDMATRFUNCIONARIO, NMFUNCIONARIO, DTADMISSAO, CDCARGO, UFPROJ) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$matricula, $nome, $dtadmFormatted, $cargo, $estado]);
                $inserted++;
            }
            
            // Log a cada 1000
            if (($idx + 1) % 1000 === 0) {
                log_msg("   ... processados " . ($idx + 1) . " de $total");
            }
            
        } catch (Exception $e) {
            $errors++;
            if ($errors <= 10) {
                log_msg("   ⚠️  Erro registro #" . ($idx + 1) . " (matricula: {$matricula}): " . $e->getMessage(), 'AVISO');
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
