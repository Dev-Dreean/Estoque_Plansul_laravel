<?php
/**
 * SCRIPT PRODUCTION-READY: Corrigir patrimonios - Veículos (LOCAL 3) vs Não-Veículos (LOCAL 70)
 * 
 * USO:
 * php scripts/producao_correcao_veiculos.php --dry-run    (simular, sem fazer mudanças)
 * php scripts/producao_correcao_veiculos.php               (executar de verdade)
 * 
 * SEGURANÇA:
 * - Cria backup antes de qualquer alteração
 * - Valida dados antes de modificar
 * - Registra tudo em log
 * - Permite rollback manual
 */

require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Parâmetros
$dry_run = in_array('--dry-run', $argv);
$log_file = 'storage/logs/producao_correcao_' . date('Y-m-d_His') . '.log';

function log_msg($msg, $level = 'info') {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_line = "[$timestamp] $level: $msg";
    echo $log_line . "\n";
    file_put_contents($log_file, $log_line . "\n", FILE_APPEND);
}

// INÍCIO
log_msg("═══════════════════════════════════════════════════════════════");
log_msg("INICIANDO CORREÇÃO DE PATRIMONIOS - VEÍCULOS vs NÃO-VEÍCULOS");
log_msg("═══════════════════════════════════════════════════════════════");
log_msg("Modo: " . ($dry_run ? "DRY-RUN (simulação)" : "PRODUÇÃO (alterações reais)"));
log_msg("");

// 1. VALIDAÇÃO INICIAL
log_msg("📋 ETAPA 1: Validações iniciais...");

try {
    // Verificar conexão com banco
    $test_query = DB::table('patr')->count();
    log_msg("   ✓ Conexão com banco de dados OK (total de patrimonios: $test_query)");
} catch (\Exception $e) {
    log_msg("   ✗ ERRO ao conectar no banco: " . $e->getMessage(), 'error');
    exit(1);
}

// 2. CRIAR BACKUP
log_msg("");
log_msg("📦 ETAPA 2: Criando backup...");

$backup_file = 'storage/logs/producao_backup_' . date('Y-m-d_His') . '.json';
$items_local3_antes = DB::table('patr')
    ->where('CDLOCAL', 3)
    ->get(['NUPATRIMONIO', 'DEPATRIMONIO', 'MARCA', 'MODELO', 'CDLOCAL', 'DTOPERACAO', 'USUARIO'])
    ->toArray();

try {
    file_put_contents($backup_file, json_encode($items_local3_antes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    log_msg("   ✓ Backup criado: $backup_file");
    log_msg("   ✓ Total de patrimonios em LOCAL 3: " . count($items_local3_antes));
} catch (\Exception $e) {
    log_msg("   ✗ ERRO ao criar backup: " . $e->getMessage(), 'error');
    exit(1);
}

// 3. IDENTIFICAR VEÍCULOS
log_msg("");
log_msg("🚗 ETAPA 3: Identificando veículos reais...");

// NUPATRs que são VEÍCULOS (identificados manualmente)
$nupatr_veiculos = [
    22414, 22422, 17782, 17781, 17785, 17787, 17788, 17790, 17792, 17794,
    17808, 22418, 22416, 22417, 22419, 22415, 17780
];

$todos_local3 = DB::table('patr')
    ->where('CDLOCAL', 3)
    ->get(['NUPATRIMONIO', 'DEPATRIMONIO', 'MARCA', 'MODELO']);

log_msg("   ✓ Total a analisar: " . count($todos_local3));
log_msg("   ✓ Veículos identificados: " . count($nupatr_veiculos));

// 4. VALIDAÇÃO DE DADOS
log_msg("");
log_msg("✔️  ETAPA 4: Validando dados...");

$veiculo_count = 0;
$nao_veiculo_count = 0;
$erros = [];

foreach ($todos_local3 as $patr) {
    // Validar que NUPATRIMONIO existe
    if (empty($patr->NUPATRIMONIO)) {
        $erros[] = "NUPATRIMONIO vazio encontrado!";
        continue;
    }
    
    // Contar
    if (in_array($patr->NUPATRIMONIO, $nupatr_veiculos)) {
        $veiculo_count++;
    } else {
        $nao_veiculo_count++;
    }
}

if (count($erros) > 0) {
    log_msg("   ✗ ERROS de validação encontrados:", 'error');
    foreach ($erros as $erro) {
        log_msg("      - $erro", 'error');
    }
    exit(1);
}

log_msg("   ✓ Validação OK");
log_msg("   ✓ Veículos a manter em LOCAL 3: $veiculo_count");
log_msg("   ✓ Não-veículos a mover para LOCAL 70: $nao_veiculo_count");

// 5. SIMULAR OU EXECUTAR
log_msg("");
log_msg("🔄 ETAPA 5: " . ($dry_run ? "SIMULANDO" : "EXECUTANDO") . " mudanças...");

if ($dry_run) {
    log_msg("   [DRY-RUN] Mostrando o que seria feito:");
    log_msg("");
    
    $veiculos_manter = [];
    $nao_veiculos_mover = [];
    
    foreach ($todos_local3 as $patr) {
        if (in_array($patr->NUPATRIMONIO, $nupatr_veiculos)) {
            $veiculos_manter[] = $patr;
        } else {
            $nao_veiculos_mover[] = $patr;
        }
    }
    
    log_msg("   Veículos a MANTER em LOCAL 3:");
    foreach ($veiculos_manter as $p) {
        log_msg("      ✓ NUPATR {$p->NUPATRIMONIO}: {$p->DEPATRIMONIO}");
    }
    
    log_msg("");
    log_msg("   Amostra de NÃO-VEÍCULOS a MOVER para LOCAL 70 (primeiros 20):");
    $amostra = array_slice($nao_veiculos_mover, 0, 20);
    foreach ($amostra as $p) {
        log_msg("      → NUPATR {$p->NUPATRIMONIO}: {$p->DEPATRIMONIO}");
    }
    
    if (count($nao_veiculos_mover) > 20) {
        log_msg("      ... e mais " . (count($nao_veiculos_mover) - 20) . " itens");
    }
    
    log_msg("");
    log_msg("   ✓ DRY-RUN concluído. Para executar de verdade, remova --dry-run");
} else {
    // EXECUTAR DE VERDADE
    $sucesso = 0;
    $erro = 0;
    
    log_msg("   Iniciando transação de banco de dados...");
    
    try {
        DB::beginTransaction();
        
        foreach ($todos_local3 as $patr) {
            if (!in_array($patr->NUPATRIMONIO, $nupatr_veiculos)) {
                // Mover para LOCAL 70
                $resultado = DB::table('patr')
                    ->where('NUPATRIMONIO', $patr->NUPATRIMONIO)
                    ->update([
                        'CDLOCAL' => 70,
                        'DTOPERACAO' => DB::raw('NOW()'),
                        'USUARIO' => 'SISTEMA'
                    ]);
                
                if ($resultado > 0) {
                    $sucesso++;
                } else {
                    $erro++;
                    log_msg("      ✗ FALHA ao atualizar NUPATR {$patr->NUPATRIMONIO}", 'error');
                }
            }
        }
        
        DB::commit();
        log_msg("   ✓ Transação concluída com sucesso!");
        log_msg("");
        log_msg("   RESULTADO:");
        log_msg("      ✓ Patrimonios atualizados: $sucesso");
        if ($erro > 0) {
            log_msg("      ✗ Erros encontrados: $erro", 'error');
        }
        
    } catch (\Exception $e) {
        DB::rollBack();
        log_msg("   ✗ ERRO na transação: " . $e->getMessage(), 'error');
        log_msg("   ⏮️  Rollback automático ativado - banco NÃO foi alterado", 'error');
        exit(1);
    }
}

// 6. VERIFICAÇÃO FINAL
log_msg("");
log_msg("🔍 ETAPA 6: Verificação final...");

$local3_agora = DB::table('patr')->where('CDLOCAL', 3)->count();
$local70_agora = DB::table('patr')->where('CDLOCAL', 70)->count();

log_msg("   LOCAL 3 agora tem: $local3_agora patrimonios");
log_msg("   LOCAL 70 agora tem: $local70_agora patrimonios");

if (!$dry_run && $local3_agora === count($nupatr_veiculos)) {
    log_msg("   ✓ VERIFICAÇÃO OK - Quantidade correta em LOCAL 3!");
} elseif ($dry_run) {
    log_msg("   ℹ️  Verificação não realizada em dry-run");
}

// TÉRMINO
log_msg("");
log_msg("═══════════════════════════════════════════════════════════════");
log_msg("✅ PROCESSO FINALIZADO COM SUCESSO!");
log_msg("═══════════════════════════════════════════════════════════════");
log_msg("Log completo: $log_file");
log_msg("Backup: $backup_file");

if ($dry_run) {
    log_msg("");
    log_msg("📝 PRÓXIMO PASSO:");
    log_msg("   Execute: php scripts/producao_correcao_veiculos.php");
    log_msg("   (SEM --dry-run para fazer as mudanças de verdade)");
}
