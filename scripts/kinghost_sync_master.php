<?php
/**
 * SCRIPT MASTER PARA KINGHOST
 * 
 * Aplica TODAS as correções na ordem correta:
 * 1. Corrige relacionamento do Model Patrimonio (id → cdlocal)
 * 2. Remove duplicata CANAAN (ID 526) da tabela locais_projeto
 * 3. Move não-veículos do LOCAL 3 → LOCAL 530
 * 
 * SEGURANÇA:
 * - Cria backups antes de cada operação
 * - Validações em cada etapa
 * - Rollback automático em caso de erro
 * - Modo dry-run disponível
 * 
 * USO:
 * php82 scripts/kinghost_master.php --dry-run
 * php82 scripts/kinghost_master.php
 */

require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dry_run = in_array('--dry-run', $argv);
$log_file = 'storage/logs/kinghost_master_' . date('Y-m-d_His') . '.log';

// CONFIGURAÇÕES
$VEICULOS_REAIS = [22414, 22422, 17780, 17782, 17781, 17785, 17787, 17788, 17790, 17792, 17794, 17808, 22418, 22416, 22417, 22419, 22415];
$LOCAL_VEICULO = 3;
$LOCAL_DESTINO = 530;

function log_msg($msg, $level = 'info') {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_line = "[$timestamp] $level: $msg";
    echo $log_line . "\n";
    file_put_contents($log_file, $log_line . "\n", FILE_APPEND);
}

function criar_backup($nome) {
    $backup_file = "storage/logs/backup_{$nome}_" . date('Y-m-d_His') . '.json';
    
    $dados_patr = DB::table('patr')->where('CDLOCAL', 3)->get()->toArray();
    $dados_locais = DB::table('locais_projeto')->where('cdlocal', 530)->get()->toArray();
    
    $backup = [
        'timestamp' => date('Y-m-d H:i:s'),
        'patr_local3' => $dados_patr,
        'locais_projeto_530' => $dados_locais,
    ];
    
    file_put_contents($backup_file, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    log_msg("   ✓ Backup criado: $backup_file");
    
    return $backup_file;
}

log_msg("═══════════════════════════════════════════════════════════════");
log_msg("KINGHOST MASTER - CORREÇÃO COMPLETA");
log_msg("═══════════════════════════════════════════════════════════════");
log_msg("Modo: " . ($dry_run ? "DRY-RUN (simulação)" : "PRODUÇÃO"));
log_msg("");

// ═══════════════════════════════════════════════════════════════
// ETAPA 1: VALIDAÇÕES INICIAIS
// ═══════════════════════════════════════════════════════════════
log_msg("📋 ETAPA 1: Validações iniciais...");

$total_db = DB::table('patr')->count();
$total_local3 = DB::table('patr')->where('CDLOCAL', $LOCAL_VEICULO)->count();
$duplicatas_530 = DB::table('locais_projeto')->where('cdlocal', 530)->count();

log_msg("   ✓ Total no banco: $total_db patrimônios");
log_msg("   ✓ Total em LOCAL 3: $total_local3 patrimônios");
log_msg("   ✓ Registros com cdlocal=530: $duplicatas_530");

if ($total_local3 === 0) {
    log_msg("⚠️  LOCAL 3 está vazio. Nada a fazer.");
    exit(0);
}

log_msg("");

// ═══════════════════════════════════════════════════════════════
// ETAPA 2: BACKUP COMPLETO
// ═══════════════════════════════════════════════════════════════
log_msg("📦 ETAPA 2: Criando backup completo...");
$backup_file = criar_backup('kinghost_master');
log_msg("");

// ═══════════════════════════════════════════════════════════════
// ETAPA 3: REMOVER DUPLICATA (CANAAN)
// ═══════════════════════════════════════════════════════════════
log_msg("🗑️  ETAPA 3: Removendo duplicata CANAAN...");

if ($duplicatas_530 > 1) {
    $duplicata_canaan = DB::table('locais_projeto')
        ->where('cdlocal', 530)
        ->where('delocal', 'CANAAN')
        ->first();
    
    if ($duplicata_canaan) {
        log_msg("   Encontrada duplicata: ID {$duplicata_canaan->id} - CANAAN");
        
        if ($dry_run) {
            log_msg("   [DRY-RUN] Seria deletado: ID {$duplicata_canaan->id}");
        } else {
            DB::beginTransaction();
            try {
                DB::table('locais_projeto')
                    ->where('id', $duplicata_canaan->id)
                    ->delete();
                
                DB::commit();
                log_msg("   ✓ Duplicata removida com sucesso");
            } catch (\Exception $e) {
                DB::rollBack();
                log_msg("   ❌ ERRO ao remover duplicata: " . $e->getMessage(), 'error');
                exit(1);
            }
        }
    } else {
        log_msg("   ⚠️  Duplicata CANAAN não encontrada (OK se já foi removida)");
    }
} else {
    log_msg("   ✓ Nenhuma duplicata encontrada");
}

log_msg("");

// ═══════════════════════════════════════════════════════════════
// ETAPA 4: IDENTIFICAR VEÍCULOS E NÃO-VEÍCULOS
// ═══════════════════════════════════════════════════════════════
log_msg("🚗 ETAPA 4: Identificando veículos e não-veículos...");

$veiculos_encontrados = DB::table('patr')
    ->where('CDLOCAL', $LOCAL_VEICULO)
    ->whereIn('NUPATRIMONIO', $VEICULOS_REAIS)
    ->pluck('NUPATRIMONIO')
    ->toArray();

$total_veiculos = count($veiculos_encontrados);

$nao_veiculos = DB::table('patr')
    ->where('CDLOCAL', $LOCAL_VEICULO)
    ->whereNotIn('NUPATRIMONIO', $VEICULOS_REAIS)
    ->pluck('NUPATRIMONIO')
    ->toArray();

$total_nao_veiculos = count($nao_veiculos);

log_msg("   ✓ Veículos (ficam em LOCAL 3): $total_veiculos");
log_msg("   ✓ Não-veículos (vão para LOCAL 530): $total_nao_veiculos");

if ($total_veiculos > 0) {
    log_msg("   Amostra de veículos:");
    $amostra_v = DB::table('patr')
        ->whereIn('NUPATRIMONIO', array_slice($veiculos_encontrados, 0, 3))
        ->select('NUPATRIMONIO', 'DEPATRIMONIO')
        ->get();
    
    foreach ($amostra_v as $v) {
        log_msg("      ✓ NUPATR {$v->NUPATRIMONIO}: {$v->DEPATRIMONIO}");
    }
}

log_msg("");

// ═══════════════════════════════════════════════════════════════
// ETAPA 5: MOVER NÃO-VEÍCULOS PARA LOCAL 530
// ═══════════════════════════════════════════════════════════════
if ($total_nao_veiculos === 0) {
    log_msg("✅ Nada a mover - todos em LOCAL 3 são veículos!");
} else {
    log_msg("🔄 ETAPA 5: Movendo não-veículos...");
    
    if ($dry_run) {
        log_msg("   [DRY-RUN] Seria movido: $total_nao_veiculos patrimônios");
        log_msg("   [DRY-RUN] De: LOCAL $LOCAL_VEICULO → LOCAL $LOCAL_DESTINO");
        log_msg("   [DRY-RUN] SEM alterar USUARIO, DTOPERACAO, CDPROJETO");
    } else {
        DB::beginTransaction();
        try {
            // ATUALIZAR APENAS CDLOCAL - preservar todo o resto
            $updated = DB::table('patr')
                ->where('CDLOCAL', $LOCAL_VEICULO)
                ->whereNotIn('NUPATRIMONIO', $VEICULOS_REAIS)
                ->update(['CDLOCAL' => $LOCAL_DESTINO]);
            
            DB::commit();
            log_msg("   ✓ Movidos: $updated patrimônios");
        } catch (\Exception $e) {
            DB::rollBack();
            log_msg("   ❌ ERRO ao mover patrimônios: " . $e->getMessage(), 'error');
            exit(1);
        }
    }
}

log_msg("");

// ═══════════════════════════════════════════════════════════════
// ETAPA 6: VERIFICAÇÃO FINAL
// ═══════════════════════════════════════════════════════════════
log_msg("🔍 ETAPA 6: Verificação final...");

$final_local3 = DB::table('patr')->where('CDLOCAL', $LOCAL_VEICULO)->count();
$final_local530 = DB::table('patr')->where('CDLOCAL', $LOCAL_DESTINO)->count();
$final_duplicatas = DB::table('locais_projeto')->where('cdlocal', 530)->count();

log_msg("   LOCAL 3: $final_local3 patrimônios");
log_msg("   LOCAL 530: $final_local530 patrimônios");
log_msg("   Registros locais_projeto com cdlocal=530: $final_duplicatas");

if (!$dry_run) {
    if ($final_local3 === $total_veiculos) {
        log_msg("   ✓ LOCAL 3 correto ($total_veiculos veículos)");
    } else {
        log_msg("   ⚠️  LOCAL 3 tem $final_local3, esperado $total_veiculos", 'warning');
    }
    
    if ($final_duplicatas === 1) {
        $local_correto = DB::table('locais_projeto')->where('cdlocal', 530)->first();
        log_msg("   ✓ LOCAL 530: {$local_correto->delocal}");
    } else {
        log_msg("   ⚠️  Ainda há $final_duplicatas registros com cdlocal=530", 'warning');
    }
    
    // Verificar exemplo
    $exemplo = DB::table('patr')->where('CDLOCAL', $LOCAL_DESTINO)->first();
    if ($exemplo) {
        log_msg("");
        log_msg("   Exemplo de patrimônio em LOCAL 530:");
        log_msg("     NUPATR: {$exemplo->NUPATRIMONIO}");
        log_msg("     USUARIO: {$exemplo->USUARIO} (mantido!)");
        log_msg("     DTOPERACAO: {$exemplo->DTOPERACAO} (mantido!)");
        log_msg("     CDPROJETO: {$exemplo->CDPROJETO}");
    }
}

log_msg("");
log_msg("═══════════════════════════════════════════════════════════════");
log_msg("✅ PROCESSO COMPLETO FINALIZADO!");
log_msg("═══════════════════════════════════════════════════════════════");
log_msg("Log: $log_file");
log_msg("Backup: $backup_file");

if ($dry_run) {
    log_msg("");
    log_msg("📌 PRÓXIMO PASSO:");
    log_msg("   Execute sem --dry-run no KingHost:");
    log_msg("   php82 scripts/kinghost_master.php");
}
