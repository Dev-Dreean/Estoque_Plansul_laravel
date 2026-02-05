<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncKinghostData extends Command
{
    protected $signature = 'sync:kinghost-data {--dry-run : Simula a sincronização sem fazer alterações}';
    protected $description = 'Sincroniza funcionários e projetos do KingHost para banco local';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 Executando em modo DRY-RUN (sem alterações)');
        }

        $this->info('🚀 Iniciando sincronização de dados do KingHost...');

        // ============================================================================
        // 1. SINCRONIZAR FUNCIONÁRIOS
        // ============================================================================
        $this->line("\n📋 ETAPA 1: Sincronizando FUNCIONÁRIOS");
        $this->line("======================================");

        $sshCmd = 'ssh plansul@ftp.plansul.info "mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p\'A33673170a\' plansul04 -e \'SELECT CDMATRFUNCIONARIO, NMFUNCIONARIO, DTADMISSAO, CDCARGO, CODFIL, UFPROJ FROM funcionarios;\'" 2>&1';
        
        $output = shell_exec($sshCmd);
        $lines = array_filter(explode("\n", trim($output)));

        if (empty($lines) || strpos($output, 'ERROR') !== false) {
            $this->error('❌ Erro ao conectar ao KingHost:');
            $this->error($output);
            return 1;
        }

        $this->info("✓ Fetched " . count($lines) . " linhas (incluindo header)");

        // Parse TSV output
        $header = null;
        $funcionariosKinghost = [];

        foreach ($lines as $line) {
            if ($header === null) {
                $header = explode("\t", $line);
                continue;
            }
            
            $values = explode("\t", $line);
            if (count($values) < 2) continue;
            
            $funcionariosKinghost[] = [
                'CDMATRFUNCIONARIO' => trim($values[0]),
                'NMFUNCIONARIO' => trim($values[1] ?? ''),
                'DTADMISSAO' => trim($values[2] ?? null),
                'CDCARGO' => trim($values[3] ?? ''),
                'CODFIL' => trim($values[4] ?? ''),
                'UFPROJ' => trim($values[5] ?? ''),
            ];
        }

        $this->info("✓ Parsed " . count($funcionariosKinghost) . " funcionários");

        // Sincronizar
        $updated = 0;
        $created = 0;
        $errors = 0;

        foreach ($funcionariosKinghost as $func) {
            try {
                if (!$func['CDMATRFUNCIONARIO']) continue;
                
                $existing = DB::table('funcionarios')
                    ->where('CDMATRFUNCIONARIO', $func['CDMATRFUNCIONARIO'])
                    ->first();

                if ($existing) {
                    if (!$dryRun) {
                        DB::table('funcionarios')
                            ->where('CDMATRFUNCIONARIO', $func['CDMATRFUNCIONARIO'])
                            ->update([
                                'NMFUNCIONARIO' => $func['NMFUNCIONARIO'],
                                'DTADMISSAO' => $func['DTADMISSAO'],
                                'CDCARGO' => $func['CDCARGO'],
                                'CODFIL' => $func['CODFIL'],
                                'UFPROJ' => $func['UFPROJ'],
                            ]);
                    }
                    $updated++;
                } else {
                    if (!$dryRun) {
                        DB::table('funcionarios')->insert([
                            'CDMATRFUNCIONARIO' => $func['CDMATRFUNCIONARIO'],
                            'NMFUNCIONARIO' => $func['NMFUNCIONARIO'],
                            'DTADMISSAO' => $func['DTADMISSAO'],
                            'CDCARGO' => $func['CDCARGO'],
                            'CODFIL' => $func['CODFIL'],
                            'UFPROJ' => $func['UFPROJ'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $created++;
                }
            } catch (\Exception $e) {
                $this->warn("⚠️  Erro: " . $e->getMessage());
                $errors++;
            }
        }

        $localFuncionarios = DB::table('funcionarios')->count();
        $this->info("\n✅ Funcionários sincronizados:");
        $this->line("   - Criados: $created");
        $this->line("   - Atualizados: $updated");
        $this->line("   - Erros: $errors");
        $this->line("   - Total local: $localFuncionarios");

        Log::info("✅ [SYNC FUNCIONARIOS] Criados=$created, Atualizados=$updated, Erros=$errors, Total=$localFuncionarios");

        // ============================================================================
        // 2. SINCRONIZAR PROJETOS (tabfant)
        // ============================================================================
        $this->line("\n📋 ETAPA 2: Sincronizando PROJETOS");
        $this->line("===================================");

        $sshCmd = 'ssh plansul@ftp.plansul.info "mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p\'A33673170a\' plansul04 -e \'SELECT id, CDPROJETO, NOMEPROJETO FROM tabfant;\'" 2>&1';

        $output = shell_exec($sshCmd);
        $lines = array_filter(explode("\n", trim($output)));

        if (empty($lines) || strpos($output, 'ERROR') !== false) {
            $this->error('❌ Erro ao conectar ao KingHost:');
            $this->error($output);
            return 1;
        }

        $this->info("✓ Fetched " . count($lines) . " linhas (incluindo header)");

        // Parse TSV output
        $header = null;
        $projetosKinghost = [];

        foreach ($lines as $line) {
            if ($header === null) {
                $header = explode("\t", $line);
                continue;
            }
            
            $values = explode("\t", $line);
            if (count($values) < 2) continue;
            
            $projetosKinghost[] = [
                'id' => trim($values[0]),
                'CDPROJETO' => trim($values[1] ?? ''),
                'NOMEPROJETO' => trim($values[2] ?? ''),
            ];
        }

        $this->info("✓ Parsed " . count($projetosKinghost) . " projetos");

        // Sincronizar
        $updated = 0;
        $created = 0;
        $errors = 0;

        foreach ($projetosKinghost as $proj) {
            try {
                if (!$proj['id']) continue;
                
                $existing = DB::table('tabfant')
                    ->where('id', $proj['id'])
                    ->first();

                if ($existing) {
                    if (!$dryRun) {
                        DB::table('tabfant')
                            ->where('id', $proj['id'])
                            ->update([
                                'CDPROJETO' => $proj['CDPROJETO'],
                                'NOMEPROJETO' => $proj['NOMEPROJETO'],
                                'updated_at' => now(),
                            ]);
                    }
                    $updated++;
                } else {
                    if (!$dryRun) {
                        DB::table('tabfant')->insert([
                            'id' => $proj['id'],
                            'CDPROJETO' => $proj['CDPROJETO'],
                            'NOMEPROJETO' => $proj['NOMEPROJETO'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $created++;
                }
            } catch (\Exception $e) {
                $this->warn("⚠️  Erro: " . $e->getMessage());
                $errors++;
            }
        }

        $localProjetos = DB::table('tabfant')->count();
        $this->info("\n✅ Projetos sincronizados:");
        $this->line("   - Criados: $created");
        $this->line("   - Atualizados: $updated");
        $this->line("   - Erros: $errors");
        $this->line("   - Total local: $localProjetos");

        Log::info("✅ [SYNC PROJETOS] Criados=$created, Atualizados=$updated, Erros=$errors, Total=$localProjetos");

        // ============================================================================
        // 3. AUDITORIA FINAL
        // ============================================================================
        $this->line("\n📊 AUDITORIA FINAL");
        $this->line("==================");

        $this->line("Funcionários:");
        $this->line("  KingHost: " . count($funcionariosKinghost));
        $this->line("  Local: " . $localFuncionarios);
        $diff = abs(count($funcionariosKinghost) - $localFuncionarios);
        $this->line("  Status: " . ($diff === 0 ? "✅ SINCRONIZADO" : "⚠️  Diferença: $diff"));

        $this->line("\nProjetos:");
        $this->line("  KingHost: " . count($projetosKinghost));
        $this->line("  Local: " . $localProjetos);
        $diff = abs(count($projetosKinghost) - $localProjetos);
        $this->line("  Status: " . ($diff === 0 ? "✅ SINCRONIZADO" : "⚠️  Diferença: $diff"));

        $this->info("\n✅ Sincronização concluída!");
        Log::info("✅ [SYNC COMPLETO] Funcionários e Projetos sincronizados");

        return 0;
    }
}
